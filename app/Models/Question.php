<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Question extends Model
{
    protected $fillable = ['section_id', 'code', 'prompt', 'help_text', 'response_type', 'measurement_label', 'measurement_unit', 'required', 'sort_order', 'active'];

    protected function casts(): array
    {
        return ['required' => 'boolean', 'active' => 'boolean'];
    }

    public function section(): BelongsTo
    {
        return $this->belongsTo(InspectionSection::class, 'section_id');
    }

    public function features(): BelongsToMany
    {
        return $this->belongsToMany(InspectionFeature::class, 'feature_questions', 'question_id', 'feature_id')->withPivot('sort_order');
    }
}
