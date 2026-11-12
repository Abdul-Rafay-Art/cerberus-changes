<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InspectionCheck extends Model
{
    protected $fillable = ['inspection_id', 'question_id', 'section_title_snapshot', 'question_text_snapshot', 'sort_order', 'rating_code', 'notes', 'measurements', 'last_saved_at'];

    protected function casts(): array
    {
        return ['last_saved_at' => 'datetime', 'version' => 'integer'];
    }

    public function inspection(): BelongsTo
    {
        return $this->belongsTo(Inspection::class);
    }

    public function question(): BelongsTo
    {
        return $this->belongsTo(Question::class);
    }
}
