<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TeleconsultationChat extends Model
{
    use HasFactory;

    protected $fillable = [
        'teleconsultation_id',
        'sender_id',
        'sender_type',
        'message',
        'message_type',
        'file_url',
        'file_name',
        'file_type',
        'file_size',
        'is_read',
        'read_at',
        'is_edited',
        'edited_at',
        'edit_reason',
    ];

    protected $casts = [
        'is_read' => 'boolean',
        'read_at' => 'datetime',
        'is_edited' => 'boolean',
        'edited_at' => 'datetime',
    ];

    /**
     * Get the teleconsultation that owns the chat message.
     */
    public function teleconsultation(): BelongsTo
    {
        return $this->belongsTo(Teleconsultation::class);
    }

    /**
     * Get the user who sent the message.
     */
    public function sender(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sender_id');
    }

    /**
     * Scope to get messages by type.
     */
    public function scopeByType($query, $type)
    {
        return $query->where('message_type', $type);
    }

    /**
     * Scope to get unread messages.
     */
    public function scopeUnread($query)
    {
        return $query->where('is_read', false);
    }

    /**
     * Scope to get messages for a specific teleconsultation.
     */
    public function scopeForTeleconsultation($query, $teleconsultationId)
    {
        return $query->where('teleconsultation_id', $teleconsultationId);
    }

    /**
     * Mark message as read.
     */
    public function markAsRead(): bool
    {
        if ($this->is_read) {
            return false;
        }

        return $this->update([
            'is_read' => true,
            'read_at' => now(),
        ]);
    }

    /**
     * Edit message.
     */
    public function editMessage(string $newMessage, string $reason = null): bool
    {
        return $this->update([
            'message' => $newMessage,
            'is_edited' => true,
            'edited_at' => now(),
            'edit_reason' => $reason,
        ]);
    }

    /**
     * Check if message is from doctor.
     */
    public function isFromDoctor(): bool
    {
        return $this->sender_type === 'doctor';
    }

    /**
     * Check if message is from patient.
     */
    public function isFromPatient(): bool
    {
        return $this->sender_type === 'patient';
    }

    /**
     * Check if message is system message.
     */
    public function isSystemMessage(): bool
    {
        return $this->sender_type === 'system';
    }

    /**
     * Get formatted file size.
     */
    public function getFormattedFileSize(): string
    {
        if (!$this->file_size) {
            return 'N/A';
        }

        $bytes = $this->file_size;
        $units = ['B', 'KB', 'MB', 'GB'];
        
        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }
        
        return round($bytes, 2) . ' ' . $units[$i];
    }
}
