<?php

namespace App\Services\Shared;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class FileStorageService
{
    public function store(UploadedFile $file, string $directory = 'uploads'): string
    {
        // Generate tenant-scoped path
        $tenantId = app('tenant')->id ?? 'default';
        $path = "{$directory}/{$tenantId}";
        
        // Store file and return path
        return $file->store($path, 'private');
    }

    public function delete(string $filePath): bool
    {
        return Storage::disk('private')->delete($filePath);
    }

    public function url(string $filePath): string
    {
        return Storage::disk('private')->url($filePath);
    }

    public function exists(string $filePath): bool
    {
        return Storage::disk('private')->exists($filePath);
    }
}
