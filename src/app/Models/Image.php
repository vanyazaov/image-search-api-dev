<?php
// app/Models/Image.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class Image extends Model
{
    use HasFactory, HasUuids;

    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'original_filename',
        'file_path',
        'file_size',
        'mime_type',
        'title',
        'category',
        'brand',
        'meta',
        'is_active'
    ];

    protected $casts = [
        'meta' => 'array',
        'is_active' => 'boolean'
    ];

    public function getSearchableData(): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'category' => $this->category,
            'brand' => $this->brand,
            'original_filename' => $this->original_filename,
            'is_active' => $this->is_active,
            'created_at' => $this->created_at->timestamp,
        ];
    }
}
