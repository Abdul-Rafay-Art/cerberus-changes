<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class InspectionFeature extends Model
{
    protected $fillable = ['code', 'name', 'description', 'addon_price', 'addon_enabled', 'active', 'sort_order'];

    protected function casts(): array
    {
        return ['addon_price' => 'decimal:2', 'addon_enabled' => 'boolean', 'active' => 'boolean'];
    }

    public function questions(): BelongsToMany
    {
        return $this->belongsToMany(Question::class, 'feature_questions', 'feature_id', 'question_id')->withPivot('sort_order');
    }
}
