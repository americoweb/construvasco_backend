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
     * 1. Store file locally (disk=local, disk=public, etc.)
     * 2. Create DB record
     * 3. Dispatch queue job to mirror to Drive
     */
    public function store(UploadJobCardFileRequest $request, int $id): JsonResponse
    {
        $jobCard = JobCard::findOrFail($id);

        $uploadedFile = $request->file('file');
        $disk         = 'local'; // or 's3', configurable
        $folder       = "job-cards/{$jobCard->id}/files";
        $diskPath     = $uploadedFile->store($folder, $disk);

        $version = $request->input('version')
            ?? ($jobCard->files()->where('type', $request->type)->max('version') ?? 0) + 1;

        /** @var JobCardFile $file */
        $file = $jobCard->files()->create([
            'uploaded_by' => $request->user()->id,
            'type'        => $request->type,
            'file_name'   => $uploadedFile->getClientOriginalName(),
            'file_url'    => Storage::disk($disk)->url($diskPath),
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
            Storage::disk($file->disk)->delete($file->disk_path);
        }

        $file->delete();

        return response()->json(['message' => 'File deleted.']);
    }
}
