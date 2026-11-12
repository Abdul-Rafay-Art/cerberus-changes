<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InspectionPhoto extends Model
{
    protected $fillable = ['inspection_id', 'storage_key', 'original_filename', 'mime_type', 'byte_size', 'caption', 'sort_order', 'uploaded_at'];

    protected function casts(): array
    {
        return ['uploaded_at' => 'datetime'];
    }

    public function inspection(): BelongsTo
    {
        return $this->belongsTo(Inspection::class);
    }
}
