<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Article;
use App\Models\Page;
use App\Models\PageView;
use App\Models\Slider;
use App\Models\User;
use App\Services\Dashboard\DashboardPeriod;
use App\Services\MediaOverviewService;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index(Request $request, MediaOverviewService $mediaOverview)
    {
        $requested = $request->query('period');
        $firstView = $requested === 'all' && PageView::query()->exists() ? CarbonImmutable::parse(PageView::query()->min('viewed_at')) : null;
        $period = DashboardPeriod::from(is_string($requested) ? $requested : null, $firstView);
        $viewsPeriod = PageView::where('viewed_at', '>=', $period->start())->count();
        $viewsPrevious = $period->isGlobal() ? 0 : PageView::whereBetween('viewed_at', [$period->previousStart(), $period->start()->subSecond()])->count();

        $stats = [
            'articles' => Article::where('is_published', true)->count(),
            'pages' => Page::where('is_published', true)->count(),
            'sliders' => Slider::count(),
            'users' => User::count(),
            // Vues des 30 derniers jours, tous contenus confondus (actuellement
            // seul Article est tracké via HasPageViews, mais la requête reste
            // valable si d'autres modèles adoptent le trait plus tard).
            'views_30d' => PageView::where('viewed_at', '>=', now()->subDays(30))->count(),
            'views_period' => $viewsPeriod,
            'views_previous' => $viewsPrevious,
            'views_variation' => DashboardPeriod::variation($viewsPeriod, $viewsPrevious),
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

        // Vues par jour, sur les 30 derniers jours — même principe : on
        // construit tous les jours nous-mêmes pour éviter les trous silencieux
        // dans le graphique les jours sans aucune vue.

        $vuesParJour = PageView::where('viewed_at', '>=', $period->start())
            ->get(['viewed_at'])
            ->groupBy(fn (PageView $view) => $view->viewed_at->format($period->format()))
            ->map->count();

        $activiteVues = collect($period->labels())->mapWithKeys(fn ($zero, $cle) => [
            $cle => $vuesParJour->get($cle, 0),
        ]);

        // Top 5 des articles les plus consultés sur les 30 derniers jours.
        $topArticles = Article::query()
            ->withCount(['pageViews' => function ($query) use ($period) {
                $query->where('viewed_at', '>=', $period->start());
            }])
            ->orderByDesc('page_views_count')
            ->take(5)
            ->get();

        // Flux d'activité récente : derniers articles modifiés + derniers utilisateurs créés
        $recentArticles = Article::with('user')->latest('updated_at')->take(5)->get();
        $recentUsers = User::latest()->take(5)->get();

        $media = $mediaOverview->overview();

        return view('admin.dashboard', compact(
            'stats',
            'activiteMensuelle',
            'activiteVues',
            'topArticles',
            'recentArticles',
            'recentUsers',
            'media',
            'period'
        ));
    }
}
