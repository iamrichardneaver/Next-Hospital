<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TeleconsultationFile extends Model
{
    use HasFactory;

    protected $fillable = [
        'teleconsultation_id',
        'uploaded_by',
        'file_name',
        'file_path',
        'file_url',
        'file_type',
        'mime_type',
        'file_size',
        'file_category',
        'description',
        'is_shared_with_patient',
        'requires_consent',
        'consent_given',
        'consent_given_at',
        'is_encrypted',
        'encryption_key',
    ];

    protected $casts = [
        'is_shared_with_patient' => 'boolean',
        'requires_consent' => 'boolean',
        'consent_given' => 'boolean',
        'consent_given_at' => 'datetime',
        'is_encrypted' => 'boolean',
    ];

    /**
     * Get the teleconsultation that owns the file.
     */
    public function teleconsultation(): BelongsTo
    {
        return $this->belongsTo(Teleconsultation::class);
    }

    /**
     * Get the user who uploaded the file.
     */
    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    /**
     * Scope to get files by category.
     */
    public function scopeByCategory($query, $category)
    {
        return $query->where('file_category', $category);
    }

    /**
     * Scope to get shared files.
     */
    public function scopeShared($query)
    {
        return $query->where('is_shared_with_patient', true);
    }

    /**
     * Scope to get files requiring consent.
     */
    public function scopeRequiringConsent($query)
    {
        return $query->where('requires_consent', true);
    }

    /**
     * Scope to get files for a specific teleconsultation.
     */
    public function scopeForTeleconsultation($query, $teleconsultationId)
    {
        return $query->where('teleconsultation_id', $teleconsultationId);
    }

    /**
     * Give consent for file sharing.
     */
    public function giveConsent(): bool
    {
        if (!$this->requires_consent || $this->consent_given) {
            return false;
        }

        return $this->update([
            'consent_given' => true,
            'consent_given_at' => now(),
        ]);
    }

    /**
     * Revoke consent for file sharing.
     */
    public function revokeConsent(): bool
    {
        if (!$this->consent_given) {
            return false;
        }

        return $this->update([
            'consent_given' => false,
            'consent_given_at' => null,
        ]);
    }

    /**
     * Check if file can be shared with patient.
     */
    public function canBeSharedWithPatient(): bool
    {
        if (!$this->is_shared_with_patient) {
            return false;
        }

        if ($this->requires_consent && !$this->consent_given) {
            return false;
        }

        return true;
    }

    /**
     * Get formatted file size.
     */
    public function getFormattedFileSize(): string
    {
        $bytes = $this->file_size;
        $units = ['B', 'KB', 'MB', 'GB'];
        
        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }
        
        return round($bytes, 2) . ' ' . $units[$i];
    }

    /**
     * Get file extension.
     */
    public function getFileExtension(): string
    {
        return pathinfo($this->file_name, PATHINFO_EXTENSION);
    }

    /**
     * Check if file is image.
     */
    public function isImage(): bool
    {
        return in_array($this->getFileExtension(), ['jpg', 'jpeg', 'png', 'gif', 'bmp', 'webp', 'svg']);
    }

    /**
     * Check if file is document.
     */
    public function isDocument(): bool
    {
        return in_array($this->getFileExtension(), ['pdf', 'doc', 'docx', 'txt', 'rtf']);
    }

    /**
     * Check if file is video.
     */
    public function isVideo(): bool
    {
        return in_array($this->getFileExtension(), ['mp4', 'avi', 'mov', 'wmv', 'flv', 'webm']);
    }

    /**
     * Check if file is audio.
     */
    public function isAudio(): bool
    {
        return in_array($this->getFileExtension(), ['mp3', 'wav', 'aac', 'ogg', 'flac']);
    }
}
