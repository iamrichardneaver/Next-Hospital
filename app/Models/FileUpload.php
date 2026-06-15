<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FileUpload extends Model
{
    use HasFactory;

    protected $fillable = [
        'original_name',
        'filename',
        'path',
        'category',
        'mime_type',
        'size',
        'related_type',
        'related_id',
        'description',
        'is_private',
        'uploaded_by'
    ];

    protected $casts = [
        'is_private' => 'boolean'
    ];

    public function uploadedBy()
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function related()
    {
        return $this->morphTo('related', 'related_type', 'related_id');
    }
}
