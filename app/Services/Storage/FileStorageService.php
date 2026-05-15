<?php

namespace App\Services\Storage;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use InvalidArgumentException;
use RuntimeException;

class FileStorageService
{
    public function storePublicFile(UploadedFile $file, string $folder): array
    {
        return $this->storeFile($file, $folder, 'public');
    }

    public function storePrivateFile(UploadedFile $file, string $folder): array
    {
        $disk = config('filesystems.deliverables_disk', 'local');

        return $this->storeFile($file, $folder, $disk);
    }

    public function storeFile(UploadedFile $file, string $folder, string $disk): array
    {
        $path = $file->store($folder, $disk);
        $url = $disk === 'public'
            ? Storage::disk('public')->url($path)
            : null;

        return [
            'path' => $path,
            'url' => $url,
            'mime' => $file->getMimeType(),
            'size' => $file->getSize(),
            'original_name' => $file->getClientOriginalName(),
            'disk' => $disk,
        ];
    }

    public function deleteFile(string $path, string $disk): bool
    {
        return Storage::disk($disk)->delete($path);
    }

    public function getSignedUrl(string $path, string $disk, int $expiresInMinutes = 30): string
    {
        if ($disk === 'public') {
            return Storage::disk('public')->url($path);
        }

        return Storage::disk($disk)->temporaryUrl(
            $path,
            now()->addMinutes($expiresInMinutes)
        );
    }

    public function validateFile(UploadedFile $file, array $allowedMimes, int $maxKb): void
    {
        if ($file->getSize() > $maxKb * 1024) {
            throw new InvalidArgumentException("Ficheiro excede o tamanho máximo de {$maxKb}KB.");
        }

        $mime = $file->getMimeType() ?? '';
        $allowed = false;
        foreach ($allowedMimes as $pattern) {
            if ($pattern === $mime || (str_ends_with($pattern, '/*') && str_starts_with($mime, rtrim($pattern, '*')))) {
                $allowed = true;
                break;
            }
        }

        if (!$allowed) {
            throw new InvalidArgumentException('Tipo de ficheiro não permitido.');
        }
    }

    public function streamDownload(string $path, string $disk, ?string $downloadName = null)
    {
        if (!Storage::disk($disk)->exists($path)) {
            throw new RuntimeException('Ficheiro não encontrado.');
        }

        return Storage::disk($disk)->download($path, $downloadName);
    }
}
