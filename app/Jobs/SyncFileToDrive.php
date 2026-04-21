<?php

namespace App\Jobs;

use App\Models\JobCard\JobCardFile;
use App\Services\GoogleDriveService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class SyncFileToDrive implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $backoff = 60; // seconds between retries

    public function __construct(public readonly JobCardFile $file) {}

    public function handle(GoogleDriveService $drive): void
    {
        // Already synced — skip
        if ($this->file->drive_file_id) {
            return;
        }

        $absPath = Storage::disk($this->file->disk)->path($this->file->disk_path);

        if (!file_exists($absPath)) {
            Log::error("SyncFileToDrive: file not found at {$absPath} for JobCardFile#{$this->file->id}");
            $this->fail(new \RuntimeException("File not found: {$absPath}"));
            return;
        }

        // Load folder context: client name + job number
        $jobCard    = $this->file->jobCard()->with('client')->first();
        $clientName = $jobCard?->client?->name;
        $jobNumber  = $jobCard?->job_number;

        $result = $drive->upload(
            $absPath,
            $this->file->file_name,
            $this->file->mime_type ?? 'application/octet-stream',
            $clientName,
            $jobNumber
        );

        $this->file->update([
            'drive_file_id'       => $result['drive_file_id'],
            'drive_link'          => $result['drive_link'],
            'drive_download_link' => $result['drive_download_link'],
            'drive_synced_at'     => now(),
        ]);

        Log::info("SyncFileToDrive: synced JobCardFile#{$this->file->id} → Drive:{$result['drive_file_id']}");
    }

    public function failed(\Throwable $e): void
    {
        Log::error("SyncFileToDrive: permanently failed for JobCardFile#{$this->file->id}: " . $e->getMessage());
    }
}
