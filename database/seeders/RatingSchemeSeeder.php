<?php

namespace Database\Seeders;

use App\Models\RatingOption;
use Illuminate\Database\Seeder;

class RatingSchemeSeeder extends Seeder
{
    public function run(): void
    {
        foreach (config('cerberus.rating_scheme') as $index => $rating) {
            RatingOption::query()->updateOrCreate(['code' => $rating['code']], [
                'label' => $rating['label'],
                'color' => $rating['color'],
                'score' => $rating['score'],
                'active' => true,
                'sort_order' => $index + 1,
            ]);
        }
    }
}
