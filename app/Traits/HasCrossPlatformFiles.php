<?php

namespace App\Traits;

use App\Services\CrossPlatformService;

trait HasCrossPlatformFiles
{
    /**
     * Get file URL for a given path attribute
     */
    public function getFileUrl(string $pathAttribute): ?string
    {
        $path = $this->getRawOriginal($pathAttribute);

        if (!$path) {
            return null;
        }

        $crossPlatform = app(CrossPlatformService::class);
        $relativePath = CrossPlatformService::normalizeStorageRelativePath($path)
            ?? $crossPlatform->getRelativePath($path);

        if (!$relativePath) {
            return null;
        }

        return $crossPlatform->getStorageUrl($relativePath);
    }

    /**
     * Get file path for a given path attribute
     */
    public function getFilePath(string $pathAttribute): ?string
    {
        $path = $this->getRawOriginal($pathAttribute);

        if (!$path) {
            return null;
        }

        $relativePath = CrossPlatformService::normalizeStorageRelativePath($path) ?? $path;

        return CrossPlatformService::getPublicStoragePath($relativePath);
    }

    /**
     * Check if file exists for a given path attribute
     */
    public function hasFile(string $pathAttribute): bool
    {
        $path = $this->getFilePath($pathAttribute);
        
        return $path && file_exists($path);
    }

    /**
     * Get file info for a given path attribute
     */
    public function getFileInfo(string $pathAttribute): ?array
    {
        $path = $this->getFilePath($pathAttribute);
        
        if (!$path || !file_exists($path)) {
            return null;
        }

        $stat = stat($path);
        $crossPlatform = app(CrossPlatformService::class);

        return [
            'path' => $path,
            'url' => $this->getFileUrl($pathAttribute),
            'size' => $stat['size'],
            'size_human' => $crossPlatform->getHumanReadableSize($stat['size']),
            'created_at' => date('Y-m-d H:i:s', $stat['ctime']),
            'modified_at' => date('Y-m-d H:i:s', $stat['mtime']),
            'extension' => $crossPlatform->getFileExtension($path),
            'is_image' => $crossPlatform->isImage($path),
            'is_document' => $crossPlatform->isDocument($path),
        ];
    }

    /**
     * Delete file for a given path attribute
     */
    public function deleteFile(string $pathAttribute): bool
    {
        $path = $this->getFilePath($pathAttribute);
        
        if ($path && file_exists($path)) {
            $deleted = unlink($path);
            
            if ($deleted) {
                $this->setAttribute($pathAttribute, null);
                $this->save();
            }
            
            return $deleted;
        }

        return false;
    }

    /**
     * Get multiple file URLs
     */
    public function getFileUrls(array $pathAttributes): array
    {
        $urls = [];
        
        foreach ($pathAttributes as $attribute) {
            $urls[$attribute] = $this->getFileUrl($attribute);
        }
        
        return $urls;
    }

    /**
     * Get multiple file paths
     */
    public function getFilePaths(array $pathAttributes): array
    {
        $paths = [];
        
        foreach ($pathAttributes as $attribute) {
            $paths[$attribute] = $this->getFilePath($attribute);
        }
        
        return $paths;
    }

    /**
     * Get all file attributes with their info
     */
    public function getAllFileInfo(array $pathAttributes): array
    {
        $info = [];
        
        foreach ($pathAttributes as $attribute) {
            $info[$attribute] = $this->getFileInfo($attribute);
        }
        
        return $info;
    }

    /**
     * Check if any files exist
     */
    public function hasAnyFiles(array $pathAttributes): bool
    {
        foreach ($pathAttributes as $attribute) {
            if ($this->hasFile($attribute)) {
                return true;
            }
        }
        
        return false;
    }

    /**
     * Delete all files
     */
    public function deleteAllFiles(array $pathAttributes): array
    {
        $results = [];
        
        foreach ($pathAttributes as $attribute) {
            $results[$attribute] = $this->deleteFile($attribute);
        }
        
        return $results;
    }

    /**
     * Get file size for a given path attribute
     */
    public function getFileSize(string $pathAttribute): ?int
    {
        $path = $this->getFilePath($pathAttribute);
        
        if ($path && file_exists($path)) {
            return filesize($path);
        }
        
        return null;
    }

    /**
     * Get human readable file size for a given path attribute
     */
    public function getFileSizeHuman(string $pathAttribute): ?string
    {
        $size = $this->getFileSize($pathAttribute);
        
        if ($size !== null) {
            return app(CrossPlatformService::class)->getHumanReadableSize($size);
        }
        
        return null;
    }

    /**
     * Get file extension for a given path attribute
     */
    public function getFileExtension(string $pathAttribute): ?string
    {
        $path = $this->getRawOriginal($pathAttribute);
        
        if (!$path) {
            return null;
        }

        return app(CrossPlatformService::class)->getFileExtension($path);
    }

    /**
     * Check if file is image for a given path attribute
     */
    public function isFileImage(string $pathAttribute): bool
    {
        $path = $this->getRawOriginal($pathAttribute);
        
        if (!$path) {
            return false;
        }

        return app(CrossPlatformService::class)->isImage($path);
    }

    /**
     * Check if file is document for a given path attribute
     */
    public function isFileDocument(string $pathAttribute): bool
    {
        $path = $this->getRawOriginal($pathAttribute);
        
        if (!$path) {
            return false;
        }

        return app(CrossPlatformService::class)->isDocument($path);
    }
}
