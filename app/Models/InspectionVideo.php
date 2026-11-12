<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InspectionVideo extends Model
{
    protected $fillable = ['inspection_id', 'provider', 'source_url', 'video_id'];

    public function inspection(): BelongsTo
    {
        return $this->belongsTo(Inspection::class);
    }
}
