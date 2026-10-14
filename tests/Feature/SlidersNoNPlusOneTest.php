<?php

use App\Models\Media;
use App\Models\Slider;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;

uses(RefreshDatabase::class);

beforeEach(function () {
    Queue::fake();
});

function seedSlidersForQueryCount(int $count): void
{
    foreach (range(1, $count) as $i) {
        $path = 'sliders/query-count-'.$i.'.jpg';
        $slider = Slider::create(['title' => 'Slide '.$i, 'image' => $path, 'order' => $i, 'is_active' => true]);
        $slider->media()->attach(
            Media::factory()->create(['path' => $path, 'type' => 'image', 'width' => 2000, 'height' => 1000]),
            ['order' => 0]
        );
    }
}

function sliderMediaQueryCount(string $url): int
{
    $morphClass = (new Slider)->getMorphClass();

    DB::flushQueryLog();
    DB::enableQueryLog();
    test()->get($url)->assertOk();

    return collect(DB::getQueryLog())
        ->filter(fn (array $query) => str_contains($query['query'], 'mediables') && in_array($morphClass, $query['bindings'], true))
        ->count();
}

it("charge les medias des sliders de l'accueil en une seule requete", function () {
    seedSlidersForQueryCount(3);

    expect(sliderMediaQueryCount('/'))->toBe(1);
});

it('charge les medias des sliders de la liste publique en une seule requete', function () {
    seedSlidersForQueryCount(3);

    expect(sliderMediaQueryCount(route('sliders.index')))->toBe(1);
});
