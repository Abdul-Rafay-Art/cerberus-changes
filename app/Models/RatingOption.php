<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RatingOption extends Model
{
    protected $fillable = ['code', 'label', 'color', 'score', 'active', 'sort_order'];

    protected function casts(): array
    {
        return ['score' => 'integer', 'active' => 'boolean'];
    }

    public static function activeScheme(): array
    {
        $options = static::query()->where('active', true)->orderBy('sort_order')->get();

        return $options->isNotEmpty() ? $options->map(fn (self $option) => $option->only(['code', 'label', 'color', 'score']))->all() : config('cerberus.rating_scheme');
    }
}
