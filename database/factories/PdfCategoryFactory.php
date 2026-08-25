<?php

namespace Database\Factories;

use App\Models\PdfCategory;
use Illuminate\Database\Eloquent\Factories\Factory;

class PdfCategoryFactory extends Factory
{
    protected $model = PdfCategory::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->unique()->words(2, true),
        ];
    }
}