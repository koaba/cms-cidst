<?php

use App\Models\Media;
use App\Models\Slider;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

beforeEach(function () {
    Queue::fake();
});

function sliderWithMedia(string $title, int $order, ?array $mediaAttributes): Slider
{
    $path = 'sliders/'.strtolower($title).'.jpg';
    $slider = Slider::create(['title' => $title, 'image' => $path, 'order' => $order, 'is_active' => true]);

    if ($mediaAttributes !== null) {
        $slider->media()->attach(Media::factory()->create($mediaAttributes + ['path' => $path, 'type' => 'image']), ['order' => 0]);
    }

    return $slider;
}

function sliderImgTag(string $html, string $needle): string
{
    preg_match_all('/<img\b[^>]*>/s', $html, $matches);

    foreach ($matches[0] as $tag) {
        if (str_contains($tag, $needle)) {
            return $tag;
        }
    }

    return '';
}

it("affiche le carrousel de l'accueil via srcset, premier slide prioritaire", function () {
    sliderWithMedia('Alpha', 0, ['width' => 2000, 'height' => 1000, 'variants' => [480 => 'sliders/variants/alpha-480.webp']]);
    sliderWithMedia('Beta', 1, ['width' => 2000, 'height' => 1000, 'variants' => null]);

    $html = $this->get('/')->assertOk()->getContent();
    $first = sliderImgTag($html, 'sliders/alpha.jpg');
    $second = sliderImgTag($html, 'sliders/beta.jpg');

    expect($first)
        ->toContain('srcset="'.Storage::disk('public')->url('sliders/variants/alpha-480.webp').' 480w')
        ->toContain('alt="Alpha"')
        ->toContain('width="2000"')
        ->toContain('fetchpriority="high"')
        ->not->toContain('loading="lazy"')
        ->and($second)
        ->toContain('loading="lazy"')
        ->not->toContain('fetchpriority="high"');
});

it("garde l'image brute d'un slider sans media, premier slide prioritaire", function () {
    sliderWithMedia('Alpha', 0, null);

    $tag = sliderImgTag($this->get('/')->assertOk()->getContent(), 'sliders/alpha.jpg');

    expect($tag)
        ->toContain('src="'.Storage::url('sliders/alpha.jpg').'"')
        ->toContain('fetchpriority="high"')
        ->not->toContain('srcset=');
});

it('affiche la page publique des sliders via srcset, en lazy', function () {
    sliderWithMedia('Alpha', 0, ['width' => 2000, 'height' => 1000, 'variants' => [480 => 'sliders/variants/alpha-480.webp']]);

    $tag = sliderImgTag($this->get(route('sliders.index'))->assertOk()->getContent(), 'sliders/alpha.jpg');

    expect($tag)
        ->toContain('srcset="'.Storage::disk('public')->url('sliders/variants/alpha-480.webp').' 480w')
        ->toContain('alt="Alpha"')
        ->toContain('height="1000"')
        ->toContain('loading="lazy"');
});
