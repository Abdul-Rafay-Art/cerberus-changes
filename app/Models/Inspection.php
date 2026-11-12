<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Inspection extends Model
{
    protected $fillable = ['booking_id', 'status', 'started_at', 'completed_at', 'package_snapshot', 'feature_snapshot', 'rating_scheme_snapshot', 'created_by'];

    protected function casts(): array
    {
        return ['started_at' => 'datetime', 'completed_at' => 'datetime', 'package_snapshot' => 'array', 'feature_snapshot' => 'array', 'rating_scheme_snapshot' => 'array'];
    }

    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }

    public function checks(): HasMany
    {
        return $this->hasMany(InspectionCheck::class)->orderBy('sort_order');
    }

    public function photos(): HasMany
    {
        return $this->hasMany(InspectionPhoto::class)->orderBy('sort_order');
    }

    public function video(): HasOne
    {
        return $this->hasOne(InspectionVideo::class);
    }

    public function reports(): HasMany
    {
        return $this->hasMany(Report::class)->orderByDesc('version');
    }
}
