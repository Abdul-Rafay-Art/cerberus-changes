<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReportAccessToken extends Model
{
    protected $fillable = ['report_id', 'token_hash', 'expires_at', 'revoked_at', 'last_accessed_at'];

    protected function casts(): array
    {
        return ['expires_at' => 'datetime', 'revoked_at' => 'datetime', 'last_accessed_at' => 'datetime'];
    }

    public function report(): BelongsTo
    {
        return $this->belongsTo(Report::class);
    }
}
