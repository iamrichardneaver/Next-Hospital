<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EyeTestComment extends Model
{
    use HasFactory;

    protected $fillable = [
        'test_request_id',
        'test_result_id',
        'comment_type',
        'comment_content',
        'commented_by',
        'commented_at',
        'is_public',
    ];

    protected $casts = [
        'commented_at' => 'datetime',
        'is_public' => 'boolean',
    ];

    // Relationships
    public function testRequest(): BelongsTo
    {
        return $this->belongsTo(EyeTestRequest::class, 'test_request_id');
    }

    public function testResult(): BelongsTo
    {
        return $this->belongsTo(EyeTestResult::class, 'test_result_id');
    }

    public function commentedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'commented_by');
    }

    // Scopes
    public function scopeByType($query, $commentType)
    {
        return $query->where('comment_type', $commentType);
    }

    public function scopePublic($query)
    {
        return $query->where('is_public', true);
    }

    public function scopePrivate($query)
    {
        return $query->where('is_public', false);
    }

    public function scopeByTestRequest($query, $testRequestId)
    {
        return $query->where('test_request_id', $testRequestId);
    }

    public function scopeByTestResult($query, $testResultId)
    {
        return $query->where('test_result_id', $testResultId);
    }

    public function scopeClinical($query)
    {
        return $query->where('comment_type', 'clinical');
    }

    public function scopeTechnical($query)
    {
        return $query->where('comment_type', 'technical');
    }

    public function scopeInterpretation($query)
    {
        return $query->where('comment_type', 'interpretation');
    }

    public function scopeRecommendation($query)
    {
        return $query->where('comment_type', 'recommendation');
    }

    public function scopeFollowUp($query)
    {
        return $query->where('comment_type', 'follow_up');
    }

    // Accessors & Mutators
    public function getFormattedTypeAttribute()
    {
        return ucfirst(str_replace('_', ' ', $this->comment_type));
    }

    public function getShortContentAttribute()
    {
        return strlen($this->comment_content) > 100 
            ? substr($this->comment_content, 0, 100) . '...'
            : $this->comment_content;
    }

    public function getFormattedDateAttribute()
    {
        return $this->commented_at->format('M d, Y H:i');
    }

    // Methods
    public function isPublic(): bool
    {
        return $this->is_public;
    }

    public function isPrivate(): bool
    {
        return !$this->is_public;
    }

    public function isClinical(): bool
    {
        return $this->comment_type === 'clinical';
    }

    public function isTechnical(): bool
    {
        return $this->comment_type === 'technical';
    }

    public function isInterpretation(): bool
    {
        return $this->comment_type === 'interpretation';
    }

    public function isRecommendation(): bool
    {
        return $this->comment_type === 'recommendation';
    }

    public function isFollowUp(): bool
    {
        return $this->comment_type === 'follow_up';
    }

    public function getCommentTypeIcon(): string
    {
        $icons = [
            'clinical' => 'fas fa-stethoscope',
            'technical' => 'fas fa-cogs',
            'interpretation' => 'fas fa-search',
            'recommendation' => 'fas fa-lightbulb',
            'follow_up' => 'fas fa-calendar-check',
        ];

        return $icons[$this->comment_type] ?? 'fas fa-comment';
    }

    public function getCommentTypeColor(): string
    {
        $colors = [
            'clinical' => 'primary',
            'technical' => 'info',
            'interpretation' => 'warning',
            'recommendation' => 'success',
            'follow_up' => 'secondary',
        ];

        return $colors[$this->comment_type] ?? 'light';
    }

    public function getCommentTypeBadgeClass(): string
    {
        $classes = [
            'clinical' => 'badge-light-primary',
            'technical' => 'badge-light-info',
            'interpretation' => 'badge-light-warning',
            'recommendation' => 'badge-light-success',
            'follow_up' => 'badge-light-secondary',
        ];

        return $classes[$this->comment_type] ?? 'badge-light-light';
    }

    public function makePublic(): bool
    {
        $this->update(['is_public' => true]);
        return true;
    }

    public function makePrivate(): bool
    {
        $this->update(['is_public' => false]);
        return true;
    }

    public function updateContent(string $content): bool
    {
        $this->update(['comment_content' => $content]);
        return true;
    }

    public function getContentLength(): int
    {
        return strlen($this->comment_content);
    }

    public function isLongComment(): bool
    {
        return $this->getContentLength() > 200;
    }

    public function getWordCount(): int
    {
        return str_word_count($this->comment_content);
    }

    public function hasMentionedUser(int $userId): bool
    {
        return strpos($this->comment_content, '@user:' . $userId) !== false;
    }

    public function getMentionedUsers(): array
    {
        preg_match_all('/@user:(\d+)/', $this->comment_content, $matches);
        return $matches[1] ?? [];
    }

    public function replaceUserMentions(): string
    {
        $content = $this->comment_content;
        $mentionedUsers = $this->getMentionedUsers();
        
        foreach ($mentionedUsers as $userId) {
            $user = User::find($userId);
            if ($user) {
                $content = str_replace('@user:' . $userId, '@' . $user->firstname . ' ' . $user->lastname, $content);
            }
        }
        
        return $content;
    }

    public function getDisplayContent(): string
    {
        return $this->replaceUserMentions();
    }

    public function getCommentTypeDescription(): string
    {
        $descriptions = [
            'clinical' => 'Clinical observations and findings',
            'technical' => 'Technical notes and equipment details',
            'interpretation' => 'Result interpretation and analysis',
            'recommendation' => 'Recommendations and next steps',
            'follow_up' => 'Follow-up instructions and scheduling',
        ];

        return $descriptions[$this->comment_type] ?? 'General comment';
    }
}
