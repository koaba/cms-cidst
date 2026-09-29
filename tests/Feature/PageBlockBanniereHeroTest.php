<?php
use App\Models\Media;
use App\Models\Page;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    Role::firstOrCreate(['name' => 'Super Admin']);
    $this->admin = User::factory()->create();
    $this->admin->assignRole('Super Admin');
    Storage::fake('public');
});

it('cree un bloc hero avec image et titre', function () {
    $page = Page::factory()->create();

    $response = $this->actingAs($this->admin)->post(
        route('admin.pages.blocks.store', $page),
        [
            'type' => 'banniere_hero',
            'titre' => 'Bienvenue',
            'image' => UploadedFile::fake()->image('hero.jpg'),
        ]
    );

    $response->assertRedirect(route('admin.pages.blocks.index', $page));
    $block = $page->blocks()->whereNull('parent_id')->first();

    expect($block->type)->toBe('banniere_hero');
    expect($block->data['titre'])->toBe('Bienvenue');
    expect($block->media()->count())->toBe(1);
});

it('rejette un hero sans image a la creation', function () {
    $page = Page::factory()->create();

    $response = $this->actingAs($this->admin)->post(
        route('admin.pages.blocks.store', $page),
        ['type' => 'banniere_hero', 'titre' => 'Sans image']
    );

    $response->assertSessionHasErrors('image');
    expect($page->blocks()->count())->toBe(0);
});

it('rejette un hero sans titre', function () {
    $page = Page::factory()->create();

    $response = $this->actingAs($this->admin)->post(
        route('admin.pages.blocks.store', $page),
        [
            'type' => 'banniere_hero',
            'image' => UploadedFile::fake()->image('hero.jpg'),
        ]
    );

    $response->assertSessionHasErrors('titre');
});

it('caste overlay_opacity en entier', function () {
    $page = Page::factory()->create();

    $this->actingAs($this->admin)->post(
        route('admin.pages.blocks.store', $page),
        [
            'type' => 'banniere_hero',
            'titre' => 'Opacite',
            'overlay_opacity' => '65',
            'image' => UploadedFile::fake()->image('hero.jpg'),
        ]
    );

    $block = $page->blocks()->whereNull('parent_id')->first();

    expect($block->data['overlay_opacity'])->toBe(65);
});

it('applique 40 comme opacite par defaut', function () {
    $page = Page::factory()->create();

    $this->actingAs($this->admin)->post(
        route('admin.pages.blocks.store', $page),
        [
            'type' => 'banniere_hero',
            'titre' => 'Defaut',
            'image' => UploadedFile::fake()->image('hero.jpg'),
        ]
    );

    $block = $page->blocks()->whereNull('parent_id')->first();

    expect($block->data['overlay_opacity'])->toBe(40);
});

it('exige bouton_url quand bouton_texte est renseigne', function () {
    $page = Page::factory()->create();

    $response = $this->actingAs($this->admin)->post(
        route('admin.pages.blocks.store', $page),
        [
            'type' => 'banniere_hero',
            'titre' => 'Bouton',
            'bouton_texte' => 'Cliquez',
            'image' => UploadedFile::fake()->image('hero.jpg'),
        ]
    );

    $response->assertSessionHasErrors('bouton_url');
});

it('refuse de retirer l image sans en fournir une nouvelle', function () {
    $page = Page::factory()->create();

    $this->actingAs($this->admin)->post(
        route('admin.pages.blocks.store', $page),
        [
            'type' => 'banniere_hero',
            'titre' => 'Retrait',
            'image' => UploadedFile::fake()->image('hero.jpg'),
        ]
    );
    $block = $page->blocks()->whereNull('parent_id')->first();

    $response = $this->actingAs($this->admin)->put(
        route('admin.pages.blocks.update', [$page, $block->id]),
        ['titre' => 'Retrait', 'delete_image' => '1']
    );

    $response->assertSessionHasErrors('image');
    expect($block->fresh()->media()->count())->toBe(1);
});

it('remplace l image quand une nouvelle est fournie', function () {
    $page = Page::factory()->create();

    $this->actingAs($this->admin)->post(
        route('admin.pages.blocks.store', $page),
        [
            'type' => 'banniere_hero',
            'titre' => 'Remplacement',
            'image' => UploadedFile::fake()->image('avant.jpg'),
        ]
    );
    $block = $page->blocks()->whereNull('parent_id')->first();

    $this->actingAs($this->admin)->put(
        route('admin.pages.blocks.update', [$page, $block->id]),
        ['titre' => 'Remplacement', 'image' => UploadedFile::fake()->image('apres.jpg')]
    );

    $media = $block->fresh()->media()->get();

    expect($media)->toHaveCount(1);
    expect($media->first()->original_name)->toBe('apres.jpg');
});

it('ne permet pas d imbriquer un hero dans une colonne', function () {
    $page = Page::factory()->create();
    $page->blocks()->create([
        'type' => 'colonnes',
        'data' => ['column_count' => 2],
        'order' => 0,
    ]);
    $parent = $page->blocks()->whereNull('parent_id')->first();

    $response = $this->actingAs($this->admin)->post(
        route('admin.pages.blocks.columns.store', [$page, $parent->id, 0]),
        [
            'type' => 'banniere_hero',
            'titre' => 'Interdit',
            'image' => UploadedFile::fake()->image('hero.jpg'),
        ]
    );

   $response->assertNotFound();
    expect(Page::find($page->id)->blocks()->where('type', 'banniere_hero')->count())->toBe(0);
    expect(Media::count())->toBe(0);
});