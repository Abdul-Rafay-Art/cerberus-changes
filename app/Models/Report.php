<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Report extends Model
{
    protected $fillable = ['inspection_id', 'storage_key', 'version', 'generated_at', 'inspection_completed_at', 'inspection_snapshot_hash', 'generated_by'];

    protected function casts(): array
    {
        return ['generated_at' => 'datetime', 'inspection_completed_at' => 'datetime', 'version' => 'integer'];
    }

    public function inspection(): BelongsTo
    {
        return $this->belongsTo(Inspection::class);
    }

    public function accessTokens(): HasMany
    {
        return $this->hasMany(ReportAccessToken::class);
    }
}
