<?php

namespace Database\Seeders;

use App\Models\Article;
use App\Models\Category;
use App\Models\User;
use Illuminate\Database\Seeder;

class ArticleSeeder extends Seeder
{
    public function run(): void
    {
        $user = User::first();

        if (!$user) {
            $this->command->error('Aucun utilisateur trouve - creez un compte avant de lancer ce seeder.');
            return;
        }

        $categories = Category::all();

        for ($i = 1; $i <= 15; $i++) {
            $article = Article::create([
                'title' => 'Article de demonstration '.$i,
                'content' => 'Contenu de demonstration pour tester la pagination et l\'affichage des cards. Lorem ipsum dolor sit amet, consectetur adipiscing elit.',
                'is_published' => true,
                'user_id' => $user->id,
            ]);

            if ($categories->isNotEmpty()) {
                $article->categories()->attach(
                    $categories->random(rand(1, min(2, $categories->count())))->pluck('id')
                );
            }
        }

        $this->command->info('15 articles de demonstration crees.');
    }
}