<?php

use App\Jobs\GenerateMediaThumbnail;
use App\Jobs\GenerateMediaVariants;
use App\Models\Article;
use App\Models\Media;
use App\Models\Mediable;
use App\Models\Page;
use App\Models\PageBlock;
use App\Models\PageView;
use App\Models\User;
use App\Services\Dashboard\DashboardStatsService;
use App\Services\MediaOverviewService;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    Bus::fake([GenerateMediaThumbnail::class, GenerateMediaVariants::class]);
    // Milieu de mois : evite les effets de bord de fin ou de debut de mois.
    $this->travelTo(now()->startOfMonth()->addDays(14)->setTime(12, 0));
});

/** @return array<int, string> requetes SQL executees par le callback */
function dsqlQueries(Closure $callback): array
{
    $queries = [];
    DB::listen(function ($query) use (&$queries) {
        $queries[] = $query->sql;
    });

    $callback();

    return $queries;
}

function dsqlArticle(array $attributes = []): Article
{
    $user = User::query()->first() ?? User::factory()->create();

    return Article::create($attributes + [
        'title' => 'Article '.uniqid(),
        'slug' => 'article-'.uniqid(),
        'content' => 'Contenu',
        'user_id' => $user->id,
        'is_published' => true,
        'published_at' => now()->subDay(),
    ]);
}

function dsqlViews(Article $article, CarbonInterface $at, int $times = 1): void
{
    for ($i = 0; $i < $times; $i++) {
        PageView::create([
            'viewable_type' => Article::class,
            'viewable_id' => $article->id,
            'ip_hash' => md5(uniqid('', true)),
            'viewed_at' => $at,
        ]);
    }
}

function dsqlMedia(): Media
{
    return Media::create([
        'path' => 'pages/'.uniqid().'.jpg',
        'original_name' => 'fichier.jpg',
        'mime_type' => 'image/jpeg',
        'size' => 1000,
        'type' => 'image',
    ]);
}

function dsqlGallery(): PageBlock
{
    return Page::factory()->create()->blocks()->create(['type' => 'galerie', 'data' => [], 'order' => 0]);
}

// --- Agregations des statistiques ------------------------------------------

it('compte les vues par jour sur la periode 7 jours', function () {
    $article = dsqlArticle();
    dsqlViews($article, now(), 3);
    dsqlViews($article, now()->subDay(), 2);
    dsqlViews($article, now()->subDays(10), 4);

    $views = app(DashboardStatsService::class)->forPeriod('week')['activiteVues'];

    expect($views)->toHaveCount(7)
        ->and($views[now()->format('Y-m-d')])->toBe(3)
        ->and($views[now()->subDay()->format('Y-m-d')])->toBe(2)
        ->and($views->sum())->toBe(5);
});

it('compte les vues par mois sur la periode 12 mois', function () {
    $article = dsqlArticle();
    dsqlViews($article, now(), 2);
    dsqlViews($article, now()->startOfMonth()->subMonths(3)->addDay(), 4);
    dsqlViews($article, now()->startOfMonth()->subMonths(14), 5);

    $views = app(DashboardStatsService::class)->forPeriod('year')['activiteVues'];

    expect($views)->toHaveCount(12)
        ->and($views[now()->format('Y-m')])->toBe(2)
        ->and($views[now()->startOfMonth()->subMonths(3)->format('Y-m')])->toBe(4)
        ->and($views->sum())->toBe(6);
});

it('compte les articles publies par mois sur six mois', function () {
    dsqlArticle(['published_at' => now()]);
    dsqlArticle(['published_at' => now()->subDay()]);
    dsqlArticle(['published_at' => now()->startOfMonth()->subMonths(2)->addDay()]);
    dsqlArticle(['published_at' => now()->startOfMonth()->subMonths(8)]);
    dsqlArticle(['is_published' => false, 'published_at' => now()]);

    $activity = app(DashboardStatsService::class)->forPeriod('month')['activiteMensuelle'];

    expect($activity)->toHaveCount(6)
        ->and($activity[now()->format('Y-m')])->toBe(2)
        ->and($activity[now()->startOfMonth()->subMonths(2)->format('Y-m')])->toBe(1)
        ->and($activity->sum())->toBe(3);
});

it('ne charge plus chaque vue ni chaque date d article en memoire', function () {
    $article = dsqlArticle();
    dsqlViews($article, now(), 3);

    $queries = dsqlQueries(fn () => app(DashboardStatsService::class)->forPeriod('week'));

    $rawViews = array_filter($queries, fn (string $sql) => preg_match('/select\s+"viewed_at"\s+from\s+"page_views"/i', $sql));
    $rawDates = array_filter($queries, fn (string $sql) => preg_match('/select\s+"published_at"\s+from\s+"articles"/i', $sql));

    expect($rawViews)->toBeEmpty()
        ->and($rawDates)->toBeEmpty();
});

it('garde un nombre de requetes constant quand le volume de vues augmente', function () {
    $article = dsqlArticle();
    dsqlViews($article, now(), 2);
    app(DashboardStatsService::class)->forPeriod('week');

    $before = count(dsqlQueries(fn () => app(DashboardStatsService::class)->forPeriod('week')));

    foreach (range(0, 6) as $day) {
        dsqlViews($article, now()->subDays($day), 30);
    }

    $after = count(dsqlQueries(fn () => app(DashboardStatsService::class)->forPeriod('week')));

    expect($after)->toBe($before);
});

// --- Alt manquants des galeries --------------------------------------------

it('compte en SQL les alt de galerie vides, nuls ou faits d espaces', function () {
    $galerie = dsqlGallery();
    $galerie->media()->attach(dsqlMedia()->id, ['order' => 0, 'alt' => null]);
    $galerie->media()->attach(dsqlMedia()->id, ['order' => 1, 'alt' => '']);
    $galerie->media()->attach(dsqlMedia()->id, ['order' => 2, 'alt' => '   ']);
    $galerie->media()->attach(dsqlMedia()->id, ['order' => 3, 'alt' => 'Description']);

    $missing = null;
    $queries = dsqlQueries(function () use (&$missing) {
        $missing = app(MediaOverviewService::class)->overview()['alerts']['missing_alt'];
    });

    $hydrated = array_filter($queries, fn (string $sql) => str_contains($sql, 'pivot_mediable_id'));

    expect($missing)->toBe(3)
        ->and($hydrated)->toBeEmpty();
});

// --- Usages des medias ------------------------------------------------------

it('precharge les usages des medias sans requete supplementaire', function () {
    $pageA = Page::factory()->create(['title' => 'Page A']);
    $pageB = Page::factory()->create(['title' => 'Page B']);
    $article = dsqlArticle(['title' => 'Mon article']);

    foreach ([[$pageA, Page::class], [$pageB, Page::class], [$article, Article::class]] as [$model, $type]) {
        Mediable::create(['media_id' => dsqlMedia()->id, 'mediable_type' => $type, 'mediable_id' => $model->id, 'order' => 0]);
    }

    // Pivot orphelin : le contenu lie a ete supprime.
    Mediable::create(['media_id' => dsqlMedia()->id, 'mediable_type' => Page::class, 'mediable_id' => 99999, 'order' => 0]);

    $media = Media::with('mediables')->get();
    Media::preloadUsages($media);

    $usages = [];
    $queries = dsqlQueries(function () use ($media, &$usages) {
        $usages = $media->map->usages()->all();
    });

    expect($queries)->toBeEmpty()
        ->and(collect($usages)->flatten(1)->pluck('title')->sort()->values()->all())->toBe(['Mon article', 'Page A', 'Page B'])
        ->and(collect($usages)->filter(fn ($u) => $u === [])->count())->toBe(1);
});

it('garde usages() fonctionnel sans prechargement', function () {
    $page = Page::factory()->create(['title' => 'Page seule']);
    $media = dsqlMedia();
    Mediable::create(['media_id' => $media->id, 'mediable_type' => Page::class, 'mediable_id' => $page->id, 'order' => 0]);

    expect(Media::with('mediables')->find($media->id)->usages())->toBe([['type' => 'Page', 'title' => 'Page seule']]);
});

it('liste la mediatheque admin avec un nombre de requetes independant du nombre de medias', function () {
    Role::create(['name' => 'Super Admin']);
    $admin = User::factory()->create();
    $admin->assignRole('Super Admin');

    $attach = function (int $count) {
        foreach (range(1, $count) as $ignored) {
            $page = Page::factory()->create();
            Mediable::create(['media_id' => dsqlMedia()->id, 'mediable_type' => Page::class, 'mediable_id' => $page->id, 'order' => 0]);
        }
    };

    $count = function () use ($admin) {
        return count(dsqlQueries(fn () => $this->actingAs($admin)->get(route('admin.media.index'))->assertOk()));
    };

    $attach(2);
    $count(); // echauffement (lignes par defaut, caches de permissions)
    $small = $count();

    $attach(8);
    $large = $count();

    expect($large)->toBe($small);
});

// --- Purge de page_views ----------------------------------------------------

it('purge par defaut les vues plus anciennes que la fenetre Global de 60 mois', function () {
    $article = dsqlArticle();
    dsqlViews($article, now()->subMonths(61), 3);
    dsqlViews($article, now()->subMonths(59), 2);
    dsqlViews($article, now(), 1);

    $this->artisan('page-views:prune')->assertExitCode(0);

    expect(PageView::count())->toBe(3)
        ->and(PageView::where('viewed_at', '<', now()->subMonths(60))->count())->toBe(0);
});

it('purge selon l option --months', function () {
    $article = dsqlArticle();
    dsqlViews($article, now()->subMonths(13), 2);
    dsqlViews($article, now()->subMonths(11), 3);

    $this->artisan('page-views:prune', ['--months' => 12])->assertExitCode(0);

    expect(PageView::count())->toBe(3);
});

it('refuse --months inferieur a 1 sans rien supprimer', function (string $months) {
    $article = dsqlArticle();
    dsqlViews($article, now()->subMonths(70), 2);

    $this->artisan('page-views:prune', ['--months' => $months])->assertExitCode(1);

    expect(PageView::count())->toBe(2);
})->with(['zero' => '0', 'negatif' => '-3', 'texte' => 'abc']);
