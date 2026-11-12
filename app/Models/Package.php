<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Package extends Model
{
    protected $fillable = ['code', 'name', 'description', 'base_price', 'currency', 'active', 'sort_order', 'video_included', 'is_demo'];

    protected function casts(): array
    {
        return ['base_price' => 'decimal:2', 'active' => 'boolean', 'video_included' => 'boolean', 'is_demo' => 'boolean'];
    }

    public function features(): BelongsToMany
    {
        return $this->belongsToMany(InspectionFeature::class, 'package_features', 'package_id', 'feature_id')->withPivot(['included', 'sort_order']);
    }

    public function includedFeatures(): BelongsToMany
    {
        return $this->features()->wherePivot('included', true)->orderByPivot('sort_order');
    }

    public function eligibleAddons(): BelongsToMany
    {
        return $this->features()->wherePivot('included', false)
            ->where('inspection_features.active', true)
            ->where('inspection_features.addon_enabled', true)
            ->whereNotNull('inspection_features.addon_price')
            ->orderByPivot('sort_order');
    }

    public function videoEnabled(): bool
    {
        return $this->video_included;
    }

}
