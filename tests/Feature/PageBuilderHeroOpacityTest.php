<?php

use App\Blocks\Rules\BanniereHeroRules;
use App\Models\Page;
use App\Models\User;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    Role::firstOrCreate(['name' => 'Super Admin']);
    $this->admin = User::factory()->create();
    $this->admin->assignRole('Super Admin');
    $this->page = Page::factory()->create();
    $this->hero = $this->page->blocks()->create([
        'type' => 'banniere_hero',
        'data' => ['titre' => 'Hero', 'overlay_opacity' => 70],
        'order' => 0,
    ]);
});

function heroOpacitySave($test, array $data)
{
    return $test->actingAs($test->admin)->postJson(route('admin.pages.builder.save', $test->page), [
        'blocks' => [['id' => $test->hero->id, 'type' => 'banniere_hero', 'data' => ['titre' => 'Hero'] + $data]],
    ]);
}

it("revient a 40 quand l'opacite est videe (null)", function () {
    heroOpacitySave($this, ['overlay_opacity' => null])->assertOk();

    expect($this->hero->fresh()->data['overlay_opacity'])->toBe(40);
});

it('revient a 40 pour une chaine vide (casts)', function () {
    $casts = (new BanniereHeroRules)->casts(Request::create('/', 'POST', ['overlay_opacity' => '']));

    expect($casts['overlay_opacity'])->toBe(40);
});

it('garde 0 quand 0 est choisi explicitement', function () {
    heroOpacitySave($this, ['overlay_opacity' => 0])->assertOk();

    expect($this->hero->fresh()->data['overlay_opacity'])->toBe(0);
});

it('garde 0 pour la chaine "0" (casts)', function () {
    $casts = (new BanniereHeroRules)->casts(Request::create('/', 'POST', ['overlay_opacity' => '0']));

    expect($casts['overlay_opacity'])->toBe(0);
});

it("utilise 40 quand l'opacite est absente", function () {
    heroOpacitySave($this, [])->assertOk();

    expect($this->hero->fresh()->data['overlay_opacity'])->toBe(40);
});
