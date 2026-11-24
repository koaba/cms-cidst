<?php

use App\Models\Media;
use App\Models\Slider;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

beforeEach(function () {
    Storage::fake('public');
});

function msSlider(string $name = 's.jpg'): Slider
{
    UploadedFile::fake()->image($name, 800, 400)->storeAs('sliders', $name, 'public');

    return Slider::factory()->create(['image' => 'sliders/'.$name]);
}

it('cree un media et l attache au slider sans toucher a la colonne image', function () {
    $slider = msSlider();

    $this->artisan('sliders:migrate-to-media')
        ->expectsOutputToContain('Terminé : 1 migré(s), 0 ignoré(s).')
        ->expectsOutputToContain('Chaque slider traité a exactement 1 média attaché.')
        ->assertExitCode(0);

    $media = $slider->fresh()->media->first();

    expect($media)->not->toBeNull()
        ->and($media->path)->toBe('sliders/s.jpg')
        ->and($media->original_name)->toBe('s.jpg')
        ->and($slider->fresh()->media)->toHaveCount(1)
        ->and($slider->fresh()->image)->toBe('sliders/s.jpg');
});

it('est rejouable sans doublon', function () {
    msSlider();

    $this->artisan('sliders:migrate-to-media')->assertExitCode(0);
    $this->artisan('sliders:migrate-to-media')
        ->expectsOutputToContain('déjà migré, ignoré.')
        ->assertExitCode(0);

    expect(Media::count())->toBe(1);
});

it('n ecrit rien en dry-run', function () {
    $slider = msSlider();

    $this->artisan('sliders:migrate-to-media', ['--dry-run' => true])
        ->expectsOutputToContain('serait migré')
        ->assertExitCode(0);

    expect(Media::count())->toBe(0)
        ->and($slider->fresh()->media)->toHaveCount(0);
});

it('ignore un slider dont le fichier est introuvable', function () {
    $slider = Slider::factory()->create(['image' => 'sliders/absent.jpg']);

    $this->artisan('sliders:migrate-to-media')
        ->expectsOutputToContain('fichier introuvable sur le disque')
        ->expectsOutputToContain('Terminé : 0 migré(s), 1 ignoré(s).')
        ->assertExitCode(0);

    expect(Media::count())->toBe(0)
        ->and($slider->fresh()->media)->toHaveCount(0);
});

it('ne traite pas un slider sans image', function () {
    Slider::factory()->create(['image' => '']);

    $this->artisan('sliders:migrate-to-media')
        ->expectsOutput('Aucun slider avec une image à migrer.')
        ->assertExitCode(0);

    expect(Media::count())->toBe(0);
});
