<?php

namespace App\Services;

use App\Models\ContentItem;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ImageUploadService
{
    private const MAX_FILE_SIZE = 10240; // 10MB in kilobytes
    private const ALLOWED_EXTENSIONS = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
    private const UPLOAD_PATH = 'content-images';

    /**
     * Store an uploaded image for a content item
     */
    public function storeContentItemImage(ContentItem $contentItem, UploadedFile $file): ?string
    {
        if (!$this->isValidImage($file)) {
            throw new \InvalidArgumentException('Invalid image file provided');
        }

        // Delete old image if exists
        $this->deleteContentItemImage($contentItem);

        $filename = $this->generateUniqueFilename($file);
        $path = $file->storeAs(self::UPLOAD_PATH, $filename, 'public');

        if ($path) {
            // Update the content item with complete image metadata
            $contentItem->update([
                'media_path' => $path, // Updated to match spec
                'image_filename' => $file->getClientOriginalName(),
                'image_mime_type' => $file->getMimeType(),
                'image_size' => $file->getSize(),
            ]);

            return $path;
        }

        return null;
    }

    /**
     * Delete an image file and remove from content item
     */
    public function deleteContentItemImage(ContentItem $contentItem): bool
    {
        if (!$contentItem->media_path) {
            return true;
        }

        $deleted = Storage::disk('public')->delete($contentItem->media_path);

        if ($deleted) {
            $contentItem->update([
                'media_path' => null,
                'image_filename' => null,
                'image_mime_type' => null,
                'image_size' => null,
            ]);
        }

        return $deleted;
    }

    /**
     * Validate uploaded image file
     */
    private function isValidImage(UploadedFile $file): bool
    {
        // Check file size
        if ($file->getSize() > self::MAX_FILE_SIZE * 1024) {
            return false;
        }

        // Check file extension
        $extension = strtolower($file->getClientOriginalExtension());
        if (!in_array($extension, self::ALLOWED_EXTENSIONS)) {
            return false;
        }

        // Check if it's actually an image
        if (!getimagesize($file->getRealPath())) {
            return false;
        }

        return true;
    }

    /**
     * Generate a unique filename for the uploaded file
     */
    private function generateUniqueFilename(UploadedFile $file): string
    {
        $extension = $file->getClientOriginalExtension();
        $basename = Str::slug(pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME));
        $timestamp = now()->format('Y-m-d_H-i-s');
        $random = Str::random(8);
        
        return "{$basename}_{$timestamp}_{$random}.{$extension}";
    }

    /**
     * Get the public URL for an image path
     */
    public function getImageUrl(?string $imagePath): ?string
    {
        if (!$imagePath) {
            return null;
        }

        return Storage::disk('public')->url($imagePath);
    }

    /**
     * Get image file size in human readable format
     */
    public function getImageSize(?string $imagePath): ?string
    {
        if (!$imagePath || !Storage::disk('public')->exists($imagePath)) {
            return null;
        }

        $sizeBytes = Storage::disk('public')->size($imagePath);
        return $this->formatBytes($sizeBytes);
    }

    /**
     * Format bytes to human readable format
     */
    private function formatBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);

        $bytes /= pow(1024, $pow);

        return round($bytes, 2) . ' ' . $units[$pow];
    }
}