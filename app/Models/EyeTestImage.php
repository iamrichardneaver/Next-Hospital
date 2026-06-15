<?php

namespace App\Models;

use App\Services\CrossPlatformService;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EyeTestImage extends Model
{
    use HasFactory;

    protected $fillable = [
        'test_request_id',
        'parameter_id',
        'image_type',
        'image_path',
        'original_filename',
        'file_extension',
        'file_size_bytes',
        'image_metadata',
        'description',
        'is_primary',
        'sort_order',
        'uploaded_by',
    ];

    protected $casts = [
        'image_metadata' => 'array',
        'is_primary' => 'boolean',
    ];

    // Relationships
    public function testRequest(): BelongsTo
    {
        return $this->belongsTo(EyeTestRequest::class, 'test_request_id');
    }

    public function parameter(): BelongsTo
    {
        return $this->belongsTo(EyeTestParameter::class, 'parameter_id');
    }

    public function uploadedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    // Scopes
    public function scopePrimary($query)
    {
        return $query->where('is_primary', true);
    }

    public function scopeByType($query, $imageType)
    {
        return $query->where('image_type', $imageType);
    }

    public function scopeByTestRequest($query, $testRequestId)
    {
        return $query->where('test_request_id', $testRequestId);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order')->orderBy('created_at');
    }

    // Accessors & Mutators
    public function getFormattedFileSizeAttribute()
    {
        $bytes = $this->file_size_bytes;
        $units = ['B', 'KB', 'MB', 'GB'];
        
        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }
        
        return round($bytes, 2) . ' ' . $units[$i];
    }

    public function getImageUrlAttribute()
    {
        return CrossPlatformService::getStorageUrl($this->image_path);
    }

    public function getThumbnailUrlAttribute()
    {
        $path = pathinfo($this->image_path);
        $thumbnailPath = $path['dirname'] . '/thumbnails/' . $path['filename'] . '_thumb.' . $path['extension'];
        
        if (file_exists(storage_path('app/public/' . $thumbnailPath))) {
            return CrossPlatformService::getStorageUrl($thumbnailPath);
        }
        
        return $this->image_url;
    }

    public function getImageDimensionsAttribute()
    {
        $metadata = $this->image_metadata;
        if (isset($metadata['width']) && isset($metadata['height'])) {
            return $metadata['width'] . ' x ' . $metadata['height'];
        }
        return null;
    }

    // Methods
    public function isPrimary(): bool
    {
        return $this->is_primary;
    }

    public function isImage(): bool
    {
        return in_array(strtolower($this->file_extension), ['jpg', 'jpeg', 'png', 'gif', 'bmp', 'tiff', 'webp']);
    }

    public function isPdf(): bool
    {
        return strtolower($this->file_extension) === 'pdf';
    }

    public function isVideo(): bool
    {
        return in_array(strtolower($this->file_extension), ['mp4', 'avi', 'mov', 'wmv', 'flv', 'webm']);
    }

    public function getImageType(): string
    {
        return $this->image_type;
    }

    public function getImagePath(): string
    {
        return $this->image_path;
    }

    public function getFullPath(): string
    {
        return storage_path('app/public/' . $this->image_path);
    }

    public function exists(): bool
    {
        return file_exists($this->getFullPath());
    }

    public function getMetadata(): array
    {
        return $this->image_metadata ?? [];
    }

    public function getDimension(string $dimension): ?int
    {
        $metadata = $this->getMetadata();
        return $metadata[$dimension] ?? null;
    }

    public function getWidth(): ?int
    {
        return $this->getDimension('width');
    }

    public function getHeight(): ?int
    {
        return $this->getDimension('height');
    }

    public function getFileSize(): int
    {
        return $this->file_size_bytes;
    }

    public function getFileExtension(): string
    {
        return $this->file_extension;
    }

    public function getOriginalFilename(): string
    {
        return $this->original_filename;
    }

    public function setAsPrimary(): bool
    {
        // Remove primary status from other images of the same test request
        static::where('test_request_id', $this->test_request_id)
            ->where('id', '!=', $this->id)
            ->update(['is_primary' => false]);

        // Set this image as primary
        $this->update(['is_primary' => true]);
        return true;
    }

    public function generateThumbnail(int $width = 300, int $height = 300): bool
    {
        if (!$this->isImage()) {
            return false;
        }

        $fullPath = $this->getFullPath();
        if (!file_exists($fullPath)) {
            return false;
        }

        $path = pathinfo($this->image_path);
        $thumbnailDir = storage_path('app/public/' . $path['dirname'] . '/thumbnails');
        
        if (!is_dir($thumbnailDir)) {
            mkdir($thumbnailDir, 0755, true);
        }

        $thumbnailPath = $thumbnailDir . '/' . $path['filename'] . '_thumb.' . $path['extension'];

        try {
            $image = \Intervention\Image\Facades\Image::make($fullPath);
            $image->resize($width, $height, function ($constraint) {
                $constraint->aspectRatio();
                $constraint->upsize();
            });
            $image->save($thumbnailPath);
            
            return true;
        } catch (\Exception $e) {
            \Log::error('Failed to generate thumbnail: ' . $e->getMessage());
            return false;
        }
    }

    public function deleteFile(): bool
    {
        $fullPath = $this->getFullPath();
        if (file_exists($fullPath)) {
            unlink($fullPath);
        }

        // Delete thumbnail if exists
        $path = pathinfo($this->image_path);
        $thumbnailPath = storage_path('app/public/' . $path['dirname'] . '/thumbnails/' . $path['filename'] . '_thumb.' . $path['extension']);
        if (file_exists($thumbnailPath)) {
            unlink($thumbnailPath);
        }

        return true;
    }

    public function getImageTypeDisplay(): string
    {
        $types = [
            'fundus' => 'Fundus Photography',
            'oct' => 'OCT Scan',
            'visual_field' => 'Visual Field Test',
            'slit_lamp' => 'Slit Lamp Examination',
            'cornea' => 'Cornea Image',
            'retina' => 'Retina Image',
            'optic_nerve' => 'Optic Nerve Image',
            'macula' => 'Macula Image',
            'anterior_chamber' => 'Anterior Chamber',
            'lens' => 'Lens Image',
            'other' => 'Other',
        ];

        return $types[$this->image_type] ?? ucfirst(str_replace('_', ' ', $this->image_type));
    }

    public function getImageTypeIcon(): string
    {
        $icons = [
            'fundus' => 'fas fa-eye',
            'oct' => 'fas fa-search',
            'visual_field' => 'fas fa-circle',
            'slit_lamp' => 'fas fa-microscope',
            'cornea' => 'fas fa-circle-notch',
            'retina' => 'fas fa-eye',
            'optic_nerve' => 'fas fa-circle',
            'macula' => 'fas fa-dot-circle',
            'anterior_chamber' => 'fas fa-circle',
            'lens' => 'fas fa-circle',
            'other' => 'fas fa-image',
        ];

        return $icons[$this->image_type] ?? 'fas fa-image';
    }
}
