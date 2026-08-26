<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class SliderFactory extends Factory
{
    public function definition(): array
    {
        return [
            'title' => $this->faker->sentence(4),
            'subtitle' => $this->faker->sentence(8),
            'image' => 'sliders/' . $this->faker->uuid() . '.jpg',
            'link_url' => $this->faker->url(),
            'order' => 0,
            'is_active' => true,
        ];
    }
}