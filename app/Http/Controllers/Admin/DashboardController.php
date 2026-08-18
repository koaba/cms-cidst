<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Article;
use App\Models\Page;
use App\Models\Slider;
use App\Models\User;

class DashboardController extends Controller
{
    public function index()
    {
        $stats = [
            'articles' => Article::where('is_published', true)->count(),
            'pages'    => Page::where('is_published', true)->count(),
            'sliders'  => Slider::count(),
            'users'    => User::count(),
        ];

        // Articles publiés par mois, sur les 6 derniers mois — pour le graphique.
        // On construit les 6 derniers mois nous-mêmes (au lieu d'un simple groupBy)
        // pour que les mois sans aucun article publié apparaissent quand même à 0,
        // sinon le graphique aurait des trous silencieux.
        $months = collect(range(5, 0))->map(fn ($i) => now()->subMonths($i)->format('Y-m'));

        $articlesParMois = Article::where('is_published', true)
    ->whereNotNull('published_at')
    ->where('published_at', '>=', now()->subMonths(6)->startOfMonth())
    ->get(['published_at'])
    ->groupBy(fn (Article $article) => $article->published_at->format('Y-m'))
    ->map->count();

        $activiteMensuelle = $months->mapWithKeys(fn ($mois) => [
            $mois => $articlesParMois->get($mois, 0),
        ]);

        // Flux d'activité récente : derniers articles modifiés + derniers utilisateurs créés
        $recentArticles = Article::with('user')->latest('updated_at')->take(5)->get();
        $recentUsers    = User::latest()->take(5)->get();

        return view('admin.dashboard', compact('stats', 'activiteMensuelle', 'recentArticles', 'recentUsers'));
    }
}