<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class InspectionSection extends Model
{
    protected $fillable = ['code', 'title', 'description', 'sort_order', 'active'];

    public function questions(): HasMany
    {
        return $this->hasMany(Question::class, 'section_id')->orderBy('sort_order');
    }
}
