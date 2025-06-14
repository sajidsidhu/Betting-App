<?php

namespace App\Traits;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

trait HandlesFileUploads
{
    protected function uploadFile(UploadedFile $file, string $path, ?string $oldFile = null): string
    {
        // Delete old file if exists
        if ($oldFile) {
            $this->deleteFile($oldFile);
        }

        // Generate unique filename
        $filename = time() . '_' . uniqid() . '.' . $file->getClientOriginalExtension();
        
        // Store file and return path
        $filePath = $file->storeAs($path, $filename, 'public');
        
        return $filePath;
    }

    protected function deleteFile(?string $path): bool
    {
        if ($path && Storage::disk('public')->exists($path)) {
            return Storage::disk('public')->delete($path);
        }
        return false;
    }

    static function getFileUrl(?string $path): ?string
    {
        if ($path && Storage::disk('public')->exists($path)) {
            return config('app.url') . Storage::url($path);
        }
        return null;
    }
}