<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\JobCard\JobCard;
use App\Models\JobCard\JobCardFile;
use App\Enums\JobCard\JobCardFileType;
use App\Jobs\SyncFileToDrive;
use App\Services\AI\GeminiService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class JobCardDesignController extends Controller
{
    public function __construct(private GeminiService $gemini) {}

    // -----------------------------------------------------------------------
    // POST /admin/job-cards/{id}/design/generate
    // -----------------------------------------------------------------------

    public function generate(Request $request, int $id): JsonResponse
    {
        $request->validate([
            'prompt'             => 'nullable|string|max:2000',
            'logo_file_id'       => 'nullable|integer',
            'reference_file_id'  => 'nullable|integer',
        ]);

        $jobCard = JobCard::with(['client', 'items', 'files'])->findOrFail($id);

        $prompt = $request->get('prompt') ?: $this->buildAutoPrompt($jobCard);

        // Resolve logo file
        $logoBase64 = null;
        $logoMime   = 'image/png';
        if ($request->logo_file_id) {
            $f = $jobCard->files->firstWhere('id', $request->logo_file_id);
            if ($f) [$logoBase64, $logoMime] = $this->fileToBase64($f);
        }

        // Resolve reference file
        $refBase64 = null;
        $refMime   = 'image/png';
        if ($request->reference_file_id) {
            $f = $jobCard->files->firstWhere('id', $request->reference_file_id);
            if ($f) [$refBase64, $refMime] = $this->fileToBase64($f);
        }

        // Base image: use the best available image file from the job card,
        // or generate a clean white canvas if there is nothing.
        $baseBase64 = null;
        $baseMime   = 'image/png';

        if ($refBase64) {
            // Use the reference as the base so Gemini can work on top of it
            $baseBase64 = $refBase64;
            $baseMime   = $refMime;
            $refBase64  = null; // already used as base
        } elseif ($logoBase64) {
            $baseBase64 = $logoBase64;
            $baseMime   = $logoMime;
            $logoBase64 = null;
        } else {
            // Look for any image among the job card files (prefer briefing/reference types)
            $imgFile = $jobCard->files
                ->filter(fn ($f) => str_starts_with($f->mime_type ?? '', 'image/') && $f->disk_path)
                ->sortBy(fn ($f) => match ($f->type?->value ?? '') {
                    'briefing'  => 0,
                    'reference' => 1,
                    'design'    => 2,
                    default     => 3,
                })
                ->first();

            if ($imgFile) {
                [$baseBase64, $baseMime] = $this->fileToBase64($imgFile);
            } else {
                // Pure white 800×600 canvas
                $canvas = imagecreatetruecolor(800, 600);
                $white  = imagecolorallocate($canvas, 255, 255, 255);
                imagefill($canvas, 0, 0, $white);
                ob_start();
                imagepng($canvas);
                $png = ob_get_clean();
                imagedestroy($canvas);
                $baseBase64 = base64_encode($png);
                $baseMime   = 'image/png';
            }
        }

        try {
            $options = [
                'product_name' => $jobCard->title,
                'design_hint'  => $jobCard->objective ?? '',
            ];
            if ($logoBase64) {
                $options['logo_base64']     = $logoBase64;
                $options['logo_mime_type']  = $logoMime;
            }
            if ($refBase64) {
                $options['reference_image_base64']     = $refBase64;
                $options['reference_image_mime_type']  = $refMime;
            }

            $imageDataUrl = $this->gemini->generateMockupWithVision(
                $baseBase64,
                $baseMime,
                $prompt,
                $options
            );

            // Parse "data:image/png;base64,XXXX"
            if (!preg_match('/^data:([^;]+);base64,(.+)$/s', $imageDataUrl, $m)) {
                throw new \Exception('Resposta inválida da IA');
            }
            [, $mimeType, $base64Data] = $m;
            $ext  = match ($mimeType) {
                'image/jpeg' => 'jpg',
                'image/webp' => 'webp',
                default      => 'png',
            };

            // Persist to storage
            $filename = 'design_' . $jobCard->job_number . '_' . now()->format('YmdHis') . '.' . $ext;
            $diskPath = "job-cards/{$jobCard->id}/designs/{$filename}";
            Storage::disk('public')->put($diskPath, base64_decode($base64Data));

            $nextVersion = ($jobCard->files
                ->where('type', JobCardFileType::DESIGN)
                ->max('version') ?? 0) + 1;

            $file = JobCardFile::create([
                'job_card_id' => $jobCard->id,
                'uploaded_by' => auth()->id(),
                'type'        => JobCardFileType::DESIGN,
                'file_name'   => $filename,
                'file_url'    => Storage::disk('public')->url($diskPath),
                'mime_type'   => $mimeType,
                'file_size'   => strlen(base64_decode($base64Data)),
                'version'     => $nextVersion,
                'notes'       => 'Gerado por IA',
                'disk'        => 'public',
                'disk_path'   => $diskPath,
            ]);

            dispatch(new SyncFileToDrive($file));

            return response()->json([
                'data' => [
                    'image_data' => $imageDataUrl,   // data URL for instant display
                    'image_url'  => $file->file_url, // persisted URL
                    'file'       => $file->fresh(),
                    'prompt'     => $prompt,
                ]
            ]);
        } catch (\Throwable $e) {
            Log::error('JobCard design generation failed', [
                'job_card_id' => $id,
                'error'       => $e->getMessage(),
            ]);
            return response()->json(['message' => 'Erro ao gerar design: ' . $e->getMessage()], 500);
        }
    }

    // -----------------------------------------------------------------------
    // GET /admin/job-cards/{id}/design/export-pdf?file_id=X
    // -----------------------------------------------------------------------

    public function exportPdf(Request $request, int $id): \Symfony\Component\HttpFoundation\Response
    {
        $request->validate(['file_id' => 'required|integer']);

        $jobCard = JobCard::with('files')->findOrFail($id);
        /** @var JobCardFile|null $file */
        $file = $jobCard->files->firstWhere('id', (int) $request->file_id);

        if (!$file || !str_starts_with($file->mime_type ?? '', 'image/')) {
            abort(404, 'Ficheiro não encontrado ou não é uma imagem.');
        }

        $diskPath = Storage::disk('public')->path($file->disk_path);
        if (!file_exists($diskPath)) {
            abort(404, 'Ficheiro não encontrado no disco.');
        }

        $pdfContent = $this->buildPdf($diskPath, $file->file_name, $jobCard);
        $pdfName    = 'print_' . $jobCard->job_number . '_v' . $file->version . '.pdf';

        return response($pdfContent, 200, [
            'Content-Type'        => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="' . $pdfName . '"',
            'Cache-Control'       => 'no-store',
        ]);
    }

    // -----------------------------------------------------------------------
    // Private helpers
    // -----------------------------------------------------------------------

    private function fileToBase64(JobCardFile $file): array
    {
        $path = Storage::disk('public')->path($file->disk_path);
        if (!file_exists($path)) {
            throw new \RuntimeException("File not found on disk: {$file->disk_path}");
        }
        return [base64_encode(file_get_contents($path)), $file->mime_type ?? 'image/png'];
    }

    private function buildAutoPrompt(JobCard $jobCard): string
    {
        $parts = ["Cria um design profissional para: {$jobCard->title}"];

        if ($jobCard->objective) {
            $parts[] = "Objectivo: {$jobCard->objective}";
        }
        if ($jobCard->description) {
            $parts[] = "Descrição: {$jobCard->description}";
        }
        if ($jobCard->items && $jobCard->items->count()) {
            $items  = $jobCard->items->map(fn ($i) => "{$i->quantity}× {$i->product_type}")->implode(', ');
            $parts[] = "Itens: {$items}";
        }
        if ($jobCard->client?->name) {
            $parts[] = "Cliente: {$jobCard->client->name}";
        }

        return implode('. ', $parts) . '.';
    }

    // -----------------------------------------------------------------------
    // PDF generation
    // -----------------------------------------------------------------------

    private function buildPdf(string $imagePath, string $imageName, JobCard $jobCard): string
    {
        if (extension_loaded('imagick')) {
            try {
                return $this->pdfViaImagick($imagePath, $jobCard);
            } catch (\Throwable $e) {
                Log::warning('Imagick PDF failed, falling back: ' . $e->getMessage());
            }
        }
        return $this->pdfFallback($imagePath, $jobCard);
    }

    private function pdfViaImagick(string $imagePath, JobCard $jobCard): string
    {
        $im = new \Imagick($imagePath);

        // Convert to CMYK for print
        $im->transformImageColorspace(\Imagick::COLORSPACE_CMYK);

        // Print quality: 300 DPI
        $im->setImageResolution(300, 300);
        $im->setImageUnits(\Imagick::RESOLUTION_PIXELSPERINCH);

        $im->setImageFormat('pdf');
        $im->setImageProperty('Title',   $jobCard->job_number . ' — ' . $jobCard->title);
        $im->setImageProperty('Author',  'Amazing Brindes');
        $im->setImageProperty('Subject', 'Print-ready CMYK');

        $pdf = $im->getImageBlob();
        $im->destroy();
        return $pdf;
    }

    /**
     * Minimal hand-crafted PDF (no external lib) embedding the image as-is.
     * Embeds JPEG as DCTDecode (CMYK if the JPEG is already CMYK).
     * PNG is embedded as-is with DeviceRGB; good enough for proofing.
     */
    private function pdfFallback(string $imagePath, JobCard $jobCard): string
    {
        [$w, $h] = getimagesize($imagePath);
        $ext     = strtolower(pathinfo($imagePath, PATHINFO_EXTENSION));
        $isJpeg  = in_array($ext, ['jpg', 'jpeg']);

        // Try to detect CMYK JPEG (Photoshop saves them with 4 channels)
        $colorSpace = '/DeviceRGB';
        if ($isJpeg) {
            $exif = @exif_read_data($imagePath);
            // If channels = 4 it's CMYK
            $colorSpace = isset($exif['channels']) && $exif['channels'] == 4
                ? '/DeviceCMYK' : '/DeviceRGB';
        }

        $filter  = $isJpeg ? '/DCTDecode' : '/FlateDecode';
        $imgData = file_get_contents($imagePath);
        $imgLen  = strlen($imgData);

        // A4 page (595×842 pt). Scale image to fit with 20pt margins.
        $pW = 595; $pH = 842; $m = 20;
        $s  = min(($pW - 2 * $m) / $w, ($pH - 2 * $m) / $h);
        $dW = round($w * $s);
        $dH = round($h * $s);
        $x  = round(($pW - $dW) / 2);
        $y  = round(($pH - $dH) / 2);

        // Job label at top
        $label     = $jobCard->job_number . ' — ' . $jobCard->title . '  |  CMYK Print Export';
        $labelStream = "BT /F1 8 Tf 20 822 Td ({$label}) Tj ET\nq\n{$dW} 0 0 {$dH} {$x} {$y} cm\n/Im Do\nQ";
        $labelLen    = strlen($labelStream);

        $pdf = "%PDF-1.4\n";
        $off = [];

        $off[1] = strlen($pdf);
        $pdf .= "1 0 obj\n<< /Type /Catalog /Pages 2 0 R >>\nendobj\n";

        $off[2] = strlen($pdf);
        $pdf .= "2 0 obj\n<< /Type /Pages /Kids [3 0 R] /Count 1 >>\nendobj\n";

        $off[3] = strlen($pdf);
        $pdf .= "3 0 obj\n<< /Type /Page /Parent 2 0 R /MediaBox [0 0 {$pW} {$pH}] "
             . "/Contents 4 0 R /Resources << /XObject << /Im 5 0 R >> /Font << /F1 6 0 R >> >> >>\nendobj\n";

        $off[4] = strlen($pdf);
        $pdf .= "4 0 obj\n<< /Length {$labelLen} >>\nstream\n{$labelStream}\nendstream\nendobj\n";

        $off[5] = strlen($pdf);
        $pdf .= "5 0 obj\n<< /Type /XObject /Subtype /Image /Width {$w} /Height {$h} "
             . "/ColorSpace {$colorSpace} /BitsPerComponent 8 /Filter {$filter} /Length {$imgLen} >>\n"
             . "stream\n";
        $pdf .= $imgData;
        $pdf .= "\nendstream\nendobj\n";

        $off[6] = strlen($pdf);
        $pdf .= "6 0 obj\n<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica >>\nendobj\n";

        $xref = strlen($pdf);
        $pdf .= "xref\n0 7\n0000000000 65535 f \n";
        foreach ([1, 2, 3, 4, 5, 6] as $n) {
            $pdf .= str_pad($off[$n], 10, '0', STR_PAD_LEFT) . " 00000 n \n";
        }
        $pdf .= "trailer\n<< /Size 7 /Root 1 0 R >>\nstartxref\n{$xref}\n%%EOF\n";

        return $pdf;
    }
}
