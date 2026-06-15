<?php

namespace App\Models;

use App\Services\CrossPlatformService;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;

class RadiologyImage extends Model
{
    use HasFactory;

    protected $fillable = [
        'sop_instance_uid',
        'series_id',
        'instance_number',
        'file_path',
        'file_name',
        'file_size',
        'mime_type',
        'dicom_tags',
        'is_compressed'
    ];

    protected $casts = [
        'dicom_tags' => 'array',
        'is_compressed' => 'boolean'
    ];

    public function series(): BelongsTo
    {
        return $this->belongsTo(RadiologySeries::class, 'series_id');
    }

    public function study()
    {
        // RadiologyImage belongs to study through series
        return $this->series->study ?? null;
    }

    public function getFileSizeFormatted(): string
    {
        $bytes = $this->file_size;
        $units = ['B', 'KB', 'MB', 'GB'];
        
        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }
        
        return round($bytes, 2) . ' ' . $units[$i];
    }

    public function isDisplayableImage(): bool
    {
        return str_starts_with((string) $this->mime_type, 'image/');
    }

    /**
     * Store an uploaded file for a radiology series (cross-platform safe).
     */
    public static function storeUploadedFile(
        RadiologySeries $series,
        UploadedFile $file,
        int $instanceNumber,
        ?string $sopInstanceUid = null
    ): self {
        $studyId = $series->study_id;
        $extension = strtolower($file->getClientOriginalExtension() ?: 'bin');
        $fileName = Str::uuid() . '.' . $extension;
        $relativePath = "radiology/{$studyId}/{$series->id}/{$fileName}";
        $fullPath = CrossPlatformService::getPublicStoragePath($relativePath);

        if (!CrossPlatformService::saveFile($fullPath, file_get_contents($file->getRealPath()))) {
            throw new \RuntimeException("Failed to store radiology image: {$fileName}");
        }

        if (!file_exists($fullPath)) {
            throw new \RuntimeException("Radiology image was not saved correctly: {$relativePath}");
        }

        $mimeType = $file->getMimeType();
        if (in_array($extension, ['dcm', 'dicom'], true)) {
            $mimeType = 'application/dicom';
        }

        return self::create([
            'sop_instance_uid' => $sopInstanceUid ?? ('1.2.826.0.1.3680043.8.498.' . time() . '.' . $instanceNumber),
            'series_id' => $series->id,
            'instance_number' => $instanceNumber,
            'file_path' => str_replace('\\', '/', $relativePath),
            'file_name' => $fileName,
            'file_size' => $file->getSize(),
            'mime_type' => $mimeType,
            'is_compressed' => false,
        ]);
    }

    public function getFullPath(): string
    {
        $normalizedPath = str_replace('\\', '/', ltrim((string) $this->file_path, '/\\'));
        
        // Build list of possible paths to check (handles both old and new structures)
        $paths = [];
        
        // 1. Check exact path in public storage (new structure: radiology/{study_id}/{series_id}/{filename})
        $paths[] = CrossPlatformService::getPublicStoragePath($normalizedPath);
        
        // 2. Check exact path in app storage
        $paths[] = CrossPlatformService::getStoragePath($normalizedPath);
        
        // 3. If path contains "studies/series" structure, check as-is in both locations
        if (str_contains($normalizedPath, 'studies') && str_contains($normalizedPath, 'series')) {
            // Already in old format: radiology/studies/{study_id}/series/{series_id}/{filename}
            // Check both public and app storage
            $paths[] = CrossPlatformService::getPublicStoragePath($normalizedPath);
            $paths[] = CrossPlatformService::getStoragePath($normalizedPath);
        } else {
            // Convert new structure to old structure for backward compatibility
            // radiology/{study_id}/{series_id}/{filename} -> radiology/studies/{study_id}/series/{series_id}/{filename}
            if (preg_match('/^radiology\/(\d+)\/(\d+)\/(.+)$/', $normalizedPath, $matches)) {
                $oldPath = "radiology/studies/{$matches[1]}/series/{$matches[2]}/{$matches[3]}";
                $paths[] = CrossPlatformService::getPublicStoragePath($oldPath);
                $paths[] = CrossPlatformService::getStoragePath($oldPath);
            }
        }
        
        // Also check if we can get the series and study IDs from relationships
        // Load series relationship if not already loaded
        if (!$this->relationLoaded('series')) {
            $this->load('series.study');
        }
        
        if ($this->series && $this->series->study) {
            $studyId = $this->series->study->id;
            $seriesId = $this->series->id;
            $fileName = basename($normalizedPath);
            
            // Try new structure: radiology/{study_id}/{series_id}/{filename}
            $newPath = "radiology/{$studyId}/{$seriesId}/{$fileName}";
            $paths[] = CrossPlatformService::getPublicStoragePath($newPath);
            $paths[] = CrossPlatformService::getStoragePath($newPath);
            
            // Try old structure: radiology/studies/{study_id}/series/{series_id}/{filename}
            $oldPath = "radiology/studies/{$studyId}/series/{$seriesId}/{$fileName}";
            $paths[] = CrossPlatformService::getPublicStoragePath($oldPath);
            $paths[] = CrossPlatformService::getStoragePath($oldPath);
        }
        
        // Remove duplicates and check each path in order
        $paths = array_unique($paths);
        foreach ($paths as $path) {
            $normalized = CrossPlatformService::normalizePath($path);
            if (file_exists($normalized) && is_file($normalized)) {
                return $normalized;
            }
        }
        
        // Fallback: return expected public path (even if file doesn't exist)
        // This allows the serveImage route to return proper 404
        return CrossPlatformService::getPublicStoragePath($normalizedPath);
    }
    
    /**
     * Get the public URL for the image (with base URL)
     * Works dynamically in all environments: local, cloud, subdirectory, root
     * Uses dynamic route to serve files, avoiding 403 errors
     */
    public function getUrl(): string
    {
        // Use dynamic route to serve images (handles 403 errors and missing files)
        // This ensures files are served through Laravel with proper permissions
        return route('radiology.images.serve', $this->id);
    }
    
    /**
     * Get direct storage URL (for cases where direct access is needed)
     * Falls back to route if direct access fails
     */
    public function getDirectUrl(): string
    {
        $path = str_replace('\\', '/', ltrim((string) $this->file_path, '/\\'));

        return CrossPlatformService::getStorageUrl($path);
    }

    public function exists(): bool
    {
        return file_exists($this->getFullPath());
    }

    /**
     * Get base64 data URI for embedding in PDF reports (DomPDF-safe).
     */
    public function getBase64ForPdf(): ?string
    {
        if (!$this->isDisplayableImage()) {
            return null;
        }

        $path = $this->getFullPath();
        if (!file_exists($path) || !is_file($path)) {
            return null;
        }

        $imageData = file_get_contents($path);
        if ($imageData === false) {
            return null;
        }

        $mimeType = $this->mime_type;
        if (!$mimeType || !str_starts_with((string) $mimeType, 'image/')) {
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mimeType = finfo_file($finfo, $path) ?: 'image/jpeg';
            finfo_close($finfo);
        }

        return 'data:' . $mimeType . ';base64,' . base64_encode($imageData);
    }
}
