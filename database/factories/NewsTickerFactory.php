<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class NewsTickerFactory extends Factory
{
    public function definition(): array
    {
        return [
            'content'   => $this->faker->sentence(6),
            'link_url'  => null,
            'order'     => $this->faker->numberBetween(0, 10),
            'is_active' => true,
        ];
    }
}