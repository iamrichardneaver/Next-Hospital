<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LabResultComment extends Model
{
    use HasFactory;

    protected $fillable = [
        'test_result_id',
        'comment_type',
        'comment_content',
        'commented_by',
        'commented_at',
        'is_public'
    ];

    protected $casts = [
        'commented_at' => 'datetime',
        'is_public' => 'boolean'
    ];

    /**
     * Get the test result that owns this comment.
     */
    public function testResult()
    {
        return $this->belongsTo(LabTestResult::class, 'test_result_id');
    }

    /**
     * Get the user who made this comment.
     */
    public function commentedBy()
    {
        return $this->belongsTo(User::class, 'commented_by');
    }

    /**
     * Scope a query to filter by comment type.
     */
    public function scopeByType($query, $type)
    {
        return $query->where('comment_type', $type);
    }

    /**
     * Scope a query to only include public comments.
     */
    public function scopePublic($query)
    {
        return $query->where('is_public', true);
    }

    /**
     * Scope a query to only include private comments.
     */
    public function scopePrivate($query)
    {
        return $query->where('is_public', false);
    }

    /**
     * Get the comment type display name.
     */
    public function getCommentTypeDisplayName()
    {
        return ucfirst(str_replace('_', ' ', $this->comment_type));
    }

    /**
     * Get the comment type badge class.
     */
    public function getCommentTypeBadgeClass()
    {
        switch ($this->comment_type) {
            case 'clinical':
                return 'badge-primary';
            case 'technical':
                return 'badge-info';
            case 'quality_control':
                return 'badge-warning';
            case 'interpretation':
                return 'badge-success';
            case 'recommendation':
                return 'badge-secondary';
            default:
                return 'badge-light';
        }
    }

    /**
     * Get the visibility badge class.
     */
    public function getVisibilityBadgeClass()
    {
        return $this->is_public ? 'badge-success' : 'badge-secondary';
    }

    /**
     * Get the visibility text.
     */
    public function getVisibilityText()
    {
        return $this->is_public ? 'Public' : 'Private';
    }

    /**
     * Get the formatted comment content (for display).
     */
    public function getFormattedContent()
    {
        // This could be enhanced to handle rich text formatting
        return $this->comment_content;
    }

    /**
     * Get the comment preview (first 100 characters).
     */
    public function getPreview($length = 100)
    {
        $content = strip_tags($this->comment_content);
        return strlen($content) > $length ? substr($content, 0, $length) . '...' : $content;
    }
}
