<?php
namespace Database\Factories;
use App\Models\Page;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
class PageFactory extends Factory
{
    protected $model = Page::class;
    public function definition(): array
    {
        return [
            'title' => $this->faker->sentence(4),
            'content' => $this->faker->paragraphs(3, true),
            'user_id' => User::factory(),
            'is_published' => true,
        ];
    }
}