<?php

namespace App\Models;

use App\Traits\HasIdPrefix;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
class Note extends Model
{
    use HasFactory, HasIdPrefix;

    protected $entityType = 'note';

    protected $fillable = [
        'consultation_id',
        'note_type',
        'content',
        'created_by',
        'is_private',
        'is_urgent',
        'priority'
    ];

    protected $casts = [
        'is_private' => 'boolean',
        'is_urgent' => 'boolean'
    ];

    /**
     * Get the consultation that owns the note.
     */
    public function consultation(): BelongsTo
    {
        return $this->belongsTo(Consultation::class);
    }

    /**
     * Get the user who created the note.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Scope to get notes by type.
     */
    public function scopeByType($query, $type)
    {
        return $query->where('note_type', $type);
    }

    /**
     * Scope to get urgent notes.
     */
    public function scopeUrgent($query)
    {
        return $query->where('is_urgent', true);
    }

    /**
     * Scope to get private notes.
     */
    public function scopePrivate($query)
    {
        return $query->where('is_private', true);
    }

    /**
     * Scope to get public notes.
     */
    public function scopePublic($query)
    {
        return $query->where('is_private', false);
    }

    /**
     * Scope to get notes by priority.
     */
    public function scopeByPriority($query, $priority)
    {
        return $query->where('priority', $priority);
    }

    /**
     * Check if note is urgent.
     */
    public function isUrgent()
    {
        return $this->is_urgent;
    }

    /**
     * Check if note is private.
     */
    public function isPrivate()
    {
        return $this->is_private;
    }

    /**
     * Check if note is public.
     */
    public function isPublic()
    {
        return !$this->is_private;
    }

    /**
     * Get the note type display name.
     */
    public function getNoteTypeDisplayAttribute()
    {
        return ucfirst(str_replace('_', ' ', $this->note_type));
    }

    /**
     * Get the priority badge class.
     */
    public function getPriorityBadgeClass()
    {
        switch ($this->priority) {
            case 'high':
                return 'badge-danger';
            case 'medium':
                return 'badge-warning';
            case 'low':
                return 'badge-info';
            default:
                return 'badge-secondary';
        }
    }

    /**
     * Get the urgency badge class.
     */
    public function getUrgencyBadgeClass()
    {
        return $this->is_urgent ? 'badge-danger' : 'badge-success';
    }

    /**
     * Get the privacy badge class.
     */
    public function getPrivacyBadgeClass()
    {
        return $this->is_private ? 'badge-warning' : 'badge-info';
    }

    /**
     * Get the formatted content with line breaks.
     */
    public function getFormattedContentAttribute()
    {
        return nl2br(e($this->content));
    }

    /**
     * Get the truncated content.
     */
    public function getTruncatedContentAttribute()
    {
        return strlen($this->content) > 100 ? substr($this->content, 0, 100) . '...' : $this->content;
    }

    /**
     * Mark as urgent.
     */
    public function markAsUrgent()
    {
        $this->update(['is_urgent' => true]);
    }

    /**
     * Mark as not urgent.
     */
    public function markAsNotUrgent()
    {
        $this->update(['is_urgent' => false]);
    }

    /**
     * Mark as private.
     */
    public function markAsPrivate()
    {
        $this->update(['is_private' => true]);
    }

    /**
     * Mark as public.
     */
    public function markAsPublic()
    {
        $this->update(['is_private' => false]);
    }
}
