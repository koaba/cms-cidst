<?php

namespace Database\Factories;

use App\Models\PdfCategory;
use App\Models\PdfDocument;
use Illuminate\Database\Eloquent\Factories\Factory;

class PdfDocumentFactory extends Factory
{
    protected $model = PdfDocument::class;

    public function definition(): array
    {
        return [
            'title' => $this->faker->sentence(4),
            'description' => $this->faker->paragraph(),
            'pdf_category_id' => PdfCategory::factory(),
        ];
    }
}