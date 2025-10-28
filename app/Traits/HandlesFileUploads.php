<?php

namespace App\Traits;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\Request;

trait HandlesFileUploads
{
    protected string $folderName = '';
    protected string $pathColumn = '';
    protected bool $requireFile = false;

    /**
     * Handle file upload
     */
    protected function handleFileUpload(Request $request, array &$data): ?string
    {
        if (!$request->hasFile('file') || !$request->file('file')->isValid()) {
            if ($this->requireFile) {
                throw new \Exception('El documento es obligatorio.');
            }
            return null;
        }

        if (empty($this->folderName) || empty($this->pathColumn)) {
            throw new \Exception('Path o nombre de carpeta no definido para documentos.');
        }

        $file = $request->file('file');
        $folder = $this->folderName;
        $path = "documents/$folder";
        $filename = $file->hashName();

        // Store file
        $file->storeAs($path, $filename);

        return "$path/$filename";
    }

    /**
     * Delete file if exists
     */
    protected function deleteFile(?string $filePath): bool
    {
        if (!$filePath) {
            return false;
        }

        try {
            return Storage::delete($filePath);
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Update file handling
     */
    protected function updateFile(Request $request, array &$data, ?string $oldFilePath = null): ?string
    {
        // If no new file, keep the old one
        if (!$request->hasFile('file')) {
            return $oldFilePath;
        }

        // Delete old file if exists
        if ($oldFilePath) {
            $this->deleteFile($oldFilePath);
        }

        // Upload new file
        return $this->handleFileUpload($request, $data);
    }

    /**
     * Validate file requirements
     */
    protected function validateFileRequirements(Request $request): void
    {
        if ($this->requireFile && !$request->hasFile('file')) {
            throw new \Exception('El documento es obligatorio.');
        }

        if (empty($this->folderName) || empty($this->pathColumn)) {
            throw new \Exception('Path o nombre de carpeta no definido para documentos.');
        }
    }

    /**
     * Get file URL
     */
    protected function getFileUrl(?string $filePath): ?string
    {
        if (!$filePath) {
            return null;
        }

        try {
            return Storage::url($filePath);
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Check if file exists
     */
    protected function fileExists(?string $filePath): bool
    {
        if (!$filePath) {
            return false;
        }

        try {
            return Storage::exists($filePath);
        } catch (\Exception $e) {
            return false;
        }
    }
}