<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\JobCard\UploadJobCardFileRequest;
use App\Http\Resources\JobCard\JobCardFileResource;
use App\Jobs\SyncFileToDrive;
use App\Models\JobCard\JobCard;
use App\Models\JobCard\JobCardFile;
use App\Services\GoogleDriveService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;

class JobCardFileController extends Controller
{
    /**
     * GET /admin/job-cards/{id}/files
     */
    public function index(int $id): JsonResponse
    {
        $jobCard = JobCard::findOrFail($id);
        $files   = $jobCard->files()->with('uploader')->get();

        return response()->json(['data' => JobCardFileResource::collection($files)]);
    }

    /**
     * POST /admin/job-cards/{id}/files
     * 1. Store file on the public disk (storage/app/public/) so it can be served via the symlink
     *    OR served through the authenticated serve endpoint.
     * 2. Create DB record
     * 3. Dispatch queue job to mirror to Drive
     */
    public function store(UploadJobCardFileRequest $request, int $id): JsonResponse
    {
        $jobCard = JobCard::findOrFail($id);

        $uploadedFile = $request->file('file');
        $disk         = 'local'; // keep private; serve via authenticated endpoint
        $folder       = "job-cards/{$jobCard->id}/files";
        $diskPath     = $uploadedFile->store($folder, $disk);

        $version = $request->input('version')
            ?? ($jobCard->files()->where('type', $request->type)->max('version') ?? 0) + 1;

        /** @var JobCardFile $file */
        $file = $jobCard->files()->create([
            'uploaded_by' => $request->user()->id,
            'type'        => $request->type,
            'file_name'   => $uploadedFile->getClientOriginalName(),
            'file_url'    => null, // no longer used; resource generates the serve URL
            'mime_type'   => $uploadedFile->getMimeType(),
            'file_size'   => $uploadedFile->getSize(),
            'version'     => $version,
            'notes'       => $request->notes,
            'disk'        => $disk,
            'disk_path'   => $diskPath,
        ]);

        // Queue Drive sync
        SyncFileToDrive::dispatch($file)->onQueue('drive');

        $file->load('uploader');
        return response()->json(['data' => new JobCardFileResource($file)], 201);
    }

    /**
     * GET /admin/job-cards/{id}/files/{fileId}/serve
     * Stream the physical file from whichever disk it lives on (authenticated).
     */
    public function serve(int $id, int $fileId): Response
    {
        $file = JobCardFile::where('job_card_id', $id)->findOrFail($fileId);

        $disk = $file->disk ?? 'local';

        if (!$file->disk_path || !Storage::disk($disk)->exists($file->disk_path)) {
            abort(404, 'File not found on disk.');
        }

        $contents = Storage::disk($disk)->get($file->disk_path);
        $mime     = $file->mime_type ?? 'application/octet-stream';

        return response($contents, 200, [
            'Content-Type'        => $mime,
            'Content-Disposition' => 'inline; filename="' . $file->file_name . '"',
            'Cache-Control'       => 'private, max-age=3600',
            'Content-Length'      => strlen($contents),
        ]);
    }

    /**
     * DELETE /admin/job-cards/{id}/files/{fileId}
     * Remove locally + from Drive (if synced)
     */
    public function destroy(int $id, int $fileId, GoogleDriveService $drive): JsonResponse
    {
        $file = JobCardFile::where('job_card_id', $id)->findOrFail($fileId);

        // Remove from Drive
        if ($file->drive_file_id) {
            $drive->delete($file->drive_file_id);
        }

        // Remove from local disk
        if ($file->disk_path) {
            Storage::disk($file->disk ?? 'local')->delete($file->disk_path);
        }

        $file->delete();

        return response()->json(['message' => 'File deleted.']);
    }
}
