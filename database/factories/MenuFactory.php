<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class MenuFactory extends Factory
{
    public function definition(): array
    {
        return [
            'label' => $this->faker->words(2, true),
            'target' => '/',
            'order' => 0,
            'is_active' => true,
            'parent_id' => null,
        ];
    }
}