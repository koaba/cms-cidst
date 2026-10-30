<?php

namespace App\Services\Dashboard;

use App\Models\Article;
use App\Models\Page;
use App\Models\PageView;
use App\Models\Slider;
use App\Models\User;
use App\Services\MediaOverviewService;
use App\Support\SqlDate;
use Carbon\CarbonImmutable;

/**
 * Calculs du tableau de bord reserves au Super Admin.
 * Le controleur n appelle ce service que pour ce role : rien n est calcule pour les autres.
 */
class DashboardStatsService
{
    public function __construct(private MediaOverviewService $mediaOverview) {}

    public function forPeriod(?string $requested): array
    {
        $firstView = $requested === 'all' && PageView::query()->exists()
            ? CarbonImmutable::parse(PageView::query()->min('viewed_at'))
            : null;
        $period = DashboardPeriod::from($requested, $firstView);
        $viewsPeriod = PageView::where('viewed_at', '>=', $period->start())->count();
        $viewsPrevious = $period->isGlobal()
            ? 0
            : PageView::whereBetween('viewed_at', [$period->previousStart(), $period->start()->subSecond()])->count();

        $stats = [
            'articles' => Article::where('is_published', true)->count(),
            'pages' => Page::where('is_published', true)->count(),
            'sliders' => Slider::count(),
            'users' => User::count(),
            'views_30d' => PageView::where('viewed_at', '>=', now()->subDays(30))->count(),
            'views_period' => $viewsPeriod,
            'views_previous' => $viewsPrevious,
            'views_variation' => DashboardPeriod::variation($viewsPeriod, $viewsPrevious),
        ];

        // Articles publies par mois sur 6 mois : les mois vides restent a 0.
        $months = collect(range(5, 0))->map(fn ($i) => now()->subMonths($i)->format('Y-m'));
        $articlesParMois = Article::where('is_published', true)
            ->whereNotNull('published_at')
            ->where('published_at', '>=', now()->subMonths(6)->startOfMonth())
            ->selectRaw(SqlDate::bucket('published_at', 'Y-m').' as bucket, COUNT(*) as total')
            ->groupBy('bucket')
            ->pluck('total', 'bucket')
            ->map(fn ($total) => (int) $total);
        $activiteMensuelle = $months->mapWithKeys(fn ($mois) => [$mois => $articlesParMois->get($mois, 0)]);

        // Vues sur la periode : toutes les cles sont construites pour eviter les trous.
        $vuesParJour = PageView::where('viewed_at', '>=', $period->start())
            ->selectRaw(SqlDate::bucket('viewed_at', $period->format()).' as bucket, COUNT(*) as total')
            ->groupBy('bucket')
            ->pluck('total', 'bucket')
            ->map(fn ($total) => (int) $total);
        $activiteVues = collect($period->labels())->mapWithKeys(fn ($zero, $cle) => [$cle => $vuesParJour->get($cle, 0)]);

        $topArticles = Article::query()
            ->withCount(['pageViews' => fn ($query) => $query->where('viewed_at', '>=', $period->start())])
            ->orderByDesc('page_views_count')
            ->take(5)
            ->get();

        return [
            'stats' => $stats,
            'activiteMensuelle' => $activiteMensuelle,
            'activiteVues' => $activiteVues,
            'topArticles' => $topArticles,
            'recentUsers' => User::latest()->take(5)->get(),
            'media' => $this->mediaOverview->overview(),
            'period' => $period,
        ];
    }
}
