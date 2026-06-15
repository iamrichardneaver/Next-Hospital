<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver;

class FileStorageService
{
    protected $crossPlatformService;

    public function __construct(CrossPlatformService $crossPlatformService)
    {
        $this->crossPlatformService = $crossPlatformService;
    }

    /**
     * Store uploaded file with proper path handling
     */
    public function storeFile(UploadedFile $file, string $directory = 'general', string $filename = null): array
    {
        // Generate filename if not provided
        if (!$filename) {
            $filename = $this->crossPlatformService->generateUniqueFilename(
                $file->getClientOriginalName(),
                $this->crossPlatformService->getUploadPath($directory)
            );
        }

        // Get proper directory path - use storage/app/public/uploads for Laravel storage
        $uploadPath = storage_path('app/public/uploads/' . $directory);
        $this->crossPlatformService->createDirectory($uploadPath);
        $fullPath = $this->crossPlatformService->normalizePath($uploadPath . DIRECTORY_SEPARATOR . $filename);

        // Store file
        $stored = $file->move($uploadPath, $filename);

        if (!$stored) {
            throw new \Exception('Failed to store file');
        }

        // Generate relative path from storage/app/public
        $relativePath = 'uploads/' . $directory . '/' . $filename;
        $publicUrl = $this->crossPlatformService->getStorageUrl($relativePath);

        return [
            'filename' => $filename,
            'original_name' => $file->getClientOriginalName(),
            'path' => $fullPath,
            'relative_path' => $relativePath,
            'url' => $publicUrl,
            'size' => $file->getSize(),
            'mime_type' => $file->getMimeType(),
            'extension' => $this->crossPlatformService->getFileExtension($filename),
        ];
    }

    /**
     * Store image with resizing and optimization
     */
    public function storeImage(UploadedFile $file, string $directory = 'images', array $sizes = []): array
    {
        // Default sizes if none provided
        if (empty($sizes)) {
            $sizes = [
                'original' => null,
                'large' => [1200, 1200],
                'medium' => [600, 600],
                'small' => [300, 300],
                'thumbnail' => [150, 150],
            ];
        }

        $results = [];

        foreach ($sizes as $sizeName => $dimensions) {
            $filename = $this->crossPlatformService->generateUniqueFilename(
                $file->getClientOriginalName(),
                $this->crossPlatformService->getUploadPath($directory)
            );

            // Add size suffix for non-original sizes
            if ($sizeName !== 'original' && $dimensions) {
                $pathInfo = pathinfo($filename);
                $filename = $pathInfo['filename'] . '_' . $sizeName . '.' . $pathInfo['extension'];
            }

            $uploadPath = storage_path('app/public/uploads/' . $directory);
            $this->crossPlatformService->createDirectory($uploadPath);
            $fullPath = $this->crossPlatformService->normalizePath($uploadPath . DIRECTORY_SEPARATOR . $filename);

            // Create image instance
            $manager = new ImageManager(new Driver());
            $image = $manager->read($file);

            // Resize if dimensions provided
            if ($dimensions && count($dimensions) === 2) {
                $image->resize($dimensions[0], $dimensions[1]);
            }

            // Optimize image and save
            $image->toJpeg(85)->save($fullPath);

            $relativePath = 'uploads/' . $directory . '/' . $filename;
            $publicUrl = $this->crossPlatformService->getStorageUrl($relativePath);

            $results[$sizeName] = [
                'filename' => $filename,
                'path' => $fullPath,
                'relative_path' => $relativePath,
                'url' => $publicUrl,
                'size' => filesize($fullPath),
                'dimensions' => $dimensions,
            ];
        }

        return $results;
    }

    /**
     * Store document file
     */
    public function storeDocument(UploadedFile $file, string $directory = 'documents'): array
    {
        return $this->storeFile($file, $directory);
    }

    /**
     * Store video file
     */
    public function storeVideo(UploadedFile $file, string $directory = 'videos'): array
    {
        return $this->storeFile($file, $directory);
    }

    /**
     * Store audio file
     */
    public function storeAudio(UploadedFile $file, string $directory = 'audio'): array
    {
        return $this->storeFile($file, $directory);
    }

    /**
     * Delete file with proper path handling
     */
    public function deleteFile(string $path): bool
    {
        $path = $this->crossPlatformService->normalizePath($path);

        if (file_exists($path)) {
            return unlink($path);
        }

        return false;
    }

    /**
     * Delete file by relative path
     */
    public function deleteFileByRelativePath(string $relativePath): bool
    {
        $fullPath = $this->crossPlatformService->getStoragePath($relativePath);
        return $this->deleteFile($fullPath);
    }

    /**
     * Get file info
     */
    public function getFileInfo(string $path): ?array
    {
        $path = $this->crossPlatformService->normalizePath($path);

        if (!file_exists($path)) {
            return null;
        }

        $stat = stat($path);

        return [
            'path' => $path,
            'size' => $stat['size'],
            'size_human' => $this->crossPlatformService->getHumanReadableSize($stat['size']),
            'created_at' => date('Y-m-d H:i:s', $stat['ctime']),
            'modified_at' => date('Y-m-d H:i:s', $stat['mtime']),
            'is_file' => is_file($path),
            'is_directory' => is_dir($path),
            'extension' => $this->crossPlatformService->getFileExtension($path),
            'is_image' => $this->crossPlatformService->isImage($path),
            'is_document' => $this->crossPlatformService->isDocument($path),
        ];
    }

    /**
     * Copy file with proper path handling
     */
    public function copyFile(string $source, string $destination): bool
    {
        $source = $this->crossPlatformService->normalizePath($source);
        $destination = $this->crossPlatformService->normalizePath($destination);

        // Create destination directory if it doesn't exist
        $destDir = dirname($destination);
        if (!$this->crossPlatformService->createDirectory($destDir)) {
            return false;
        }

        return copy($source, $destination);
    }

    /**
     * Move file with proper path handling
     */
    public function moveFile(string $source, string $destination): bool
    {
        $source = $this->crossPlatformService->normalizePath($source);
        $destination = $this->crossPlatformService->normalizePath($destination);

        // Create destination directory if it doesn't exist
        $destDir = dirname($destination);
        if (!$this->crossPlatformService->createDirectory($destDir)) {
            return false;
        }

        return rename($source, $destination);
    }

    /**
     * Get file URL
     */
    public function getFileUrl(string $path): string
    {
        return $this->crossPlatformService->getStorageUrl($path);
    }

    /**
     * Get file content
     */
    public function getFileContent(string $path): ?string
    {
        $path = $this->crossPlatformService->normalizePath($path);

        if (!file_exists($path)) {
            return null;
        }

        return file_get_contents($path);
    }

    /**
     * Save file content
     */
    public function saveFileContent(string $path, string $content): bool
    {
        return $this->crossPlatformService->saveFile($path, $content);
    }

    /**
     * Create directory with proper permissions
     */
    public function createDirectory(string $path): bool
    {
        return $this->crossPlatformService->createDirectory($path);
    }

    /**
     * List files in directory
     */
    public function listFiles(string $directory, string $pattern = '*'): array
    {
        $directory = $this->crossPlatformService->normalizePath($directory);

        if (!is_dir($directory)) {
            return [];
        }

        $files = glob($directory . DIRECTORY_SEPARATOR . $pattern);
        $result = [];

        foreach ($files as $file) {
            if (is_file($file)) {
                $result[] = $this->getFileInfo($file);
            }
        }

        return $result;
    }

    /**
     * Get storage statistics
     */
    public function getStorageStats(): array
    {
        $uploadPath = $this->crossPlatformService->getUploadPath();
        $totalSize = 0;
        $fileCount = 0;

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($uploadPath, \RecursiveDirectoryIterator::SKIP_DOTS)
        );

        foreach ($iterator as $file) {
            if ($file->isFile()) {
                $totalSize += $file->getSize();
                $fileCount++;
            }
        }

        return [
            'total_size' => $totalSize,
            'total_size_human' => $this->crossPlatformService->getHumanReadableSize($totalSize),
            'file_count' => $fileCount,
            'upload_path' => $uploadPath,
        ];
    }

    /**
     * Clean up temporary files
     */
    public function cleanupTempFiles(int $maxAge = 3600): int
    {
        $tempPath = $this->crossPlatformService->getTempPath();
        $cleaned = 0;

        if (is_dir($tempPath)) {
            $iterator = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($tempPath, \RecursiveDirectoryIterator::SKIP_DOTS)
            );

            foreach ($iterator as $file) {
                if ($file->isFile() && (time() - $file->getMTime()) > $maxAge) {
                    if (unlink($file->getPathname())) {
                        $cleaned++;
                    }
                }
            }
        }

        return $cleaned;
    }

    /**
     * Validate file type and size
     */
    public function validateFile(UploadedFile $file, string $type = 'general'): array
    {
        $config = config('cross_platform.file_types.' . $type, []);
        $extensions = $config['extensions'] ?? [];
        $maxSize = $config['max_size'] ?? 5 * 1024 * 1024; // 5MB default

        $extension = $this->crossPlatformService->getFileExtension($file->getClientOriginalName());
        $size = $file->getSize();

        $errors = [];

        if (!empty($extensions) && !in_array($extension, $extensions)) {
            $errors[] = "File type '{$extension}' is not allowed. Allowed types: " . implode(', ', $extensions);
        }

        if ($size > $maxSize) {
            $errors[] = "File size ({$this->crossPlatformService->getHumanReadableSize($size)}) exceeds maximum allowed size ({$this->crossPlatformService->getHumanReadableSize($maxSize)})";
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors,
            'extension' => $extension,
            'size' => $size,
            'size_human' => $this->crossPlatformService->getHumanReadableSize($size),
        ];
    }
}
