<?php

use App\Models\Article;
use App\Models\Media;
use App\Models\User;
use App\Services\WatermarkService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    Storage::fake('public');
    Role::create(['name' => 'Super Admin']);
    $this->admin = User::factory()->create();
    $this->admin->assignRole('Super Admin');
});

it('ajoute un PDF a un article via upload direct', function () {
    $article = Article::factory()->create();

    $response = $this->actingAs($this->admin)->put(route('admin.articles.update', $article), [
        'title' => $article->title,
        'content' => $article->content,
        'pdfs' => [UploadedFile::fake()->create('rapport.pdf', 100, 'application/pdf')],
    ]);

    $response->assertRedirect(route('admin.articles.index'));
    expect($article->fresh()->media->where('mime_type', 'application/pdf'))->toHaveCount(1);
});

it('rejette un upload qui depasse la limite de 10 PDF par article', function () {
    $article = Article::factory()->create();

    for ($i = 0; $i < 10; $i++) {
        $media = Media::factory()->create(['mime_type' => 'application/pdf']);
        $article->media()->attach($media->id, ['order' => $i]);
    }

    $response = $this->actingAs($this->admin)->put(route('admin.articles.update', $article), [
        'title' => $article->title,
        'content' => $article->content,
        'pdfs' => [UploadedFile::fake()->create('extra.pdf', 100, 'application/pdf')],
    ]);

    $response->assertSessionHasErrors('pdfs');
    expect($article->fresh()->media()->where('mime_type', 'application/pdf')->count())->toBe(10);
});

it('autorise de remplacer un PDF supprime par un nouveau sans depasser la limite', function () {
    $article = Article::factory()->create();
    $toDelete = [];

    for ($i = 0; $i < 10; $i++) {
        $media = Media::factory()->create(['mime_type' => 'application/pdf']);
        $article->media()->attach($media->id, ['order' => $i]);
        $toDelete[] = $media->id;
    }

    $response = $this->actingAs($this->admin)->put(route('admin.articles.update', $article), [
        'title' => $article->title,
        'content' => $article->content,
        'delete_pdfs' => [$toDelete[0]],
        'pdfs' => [UploadedFile::fake()->create('nouveau.pdf', 100, 'application/pdf')],
    ]);

    $response->assertSessionHasNoErrors();
    expect($article->fresh()->media()->where('mime_type', 'application/pdf')->count())->toBe(10);
});

it('retire un PDF joint a un article', function () {
    $article = Article::factory()->create();
    $media = Media::factory()->create(['mime_type' => 'application/pdf']);
    $article->media()->attach($media->id, ['order' => 0]);

    $response = $this->actingAs($this->admin)->put(route('admin.articles.update', $article), [
        'title' => $article->title,
        'content' => $article->content,
        'delete_pdfs' => [$media->id],
    ]);

    $response->assertRedirect(route('admin.articles.index'));
    expect($article->fresh()->media()->where('mime_type', 'application/pdf')->count())->toBe(0);
});

it('ne compte pas les PDF joints dans le quota de la galerie d\'images', function () {
    $article = Article::factory()->create();

    // 5 PDF + 20 images deja au maximum : un ajout d'image doit rester bloque (quota images atteint),
    // mais le blocage ne doit pas venir d'un comptage errone qui inclurait les PDF.
    for ($i = 0; $i < 5; $i++) {
        $pdf = Media::factory()->create(['mime_type' => 'application/pdf']);
        $article->media()->attach($pdf->id, ['order' => $i]);
    }
    for ($i = 5; $i < 25; $i++) {
        $image = Media::factory()->create(['mime_type' => 'image/jpeg']);
        $article->media()->attach($image->id, ['order' => $i]);
    }

    $response = $this->actingAs($this->admin)->put(route('admin.articles.update', $article), [
        'title' => $article->title,
        'content' => $article->content,
        'images' => [UploadedFile::fake()->image('photo.jpg')],
    ]);

    // 20 images deja presentes (le max) : le nouvel envoi doit etre rejete pour la galerie,
    // et seulement pour la galerie (pas d'erreur PDF, on n'en touche pas).
    $response->assertSessionHasErrors('images');
    $response->assertSessionDoesntHaveErrors('pdfs');
});

it('ne bloque pas un ajout d\'image quand la galerie est sous quota malgre des PDF presents', function () {
    $article = Article::factory()->create();

    for ($i = 0; $i < 8; $i++) {
        $pdf = Media::factory()->create(['mime_type' => 'application/pdf']);
        $article->media()->attach($pdf->id, ['order' => $i]);
    }

    $response = $this->actingAs($this->admin)->put(route('admin.articles.update', $article), [
        'title' => $article->title,
        'content' => $article->content,
        'images' => [UploadedFile::fake()->image('photo.jpg')],
    ]);

    $response->assertSessionHasNoErrors();
    expect($article->fresh()->media()->where('mime_type', 'like', 'image/%')->count())->toBe(1);
});

it('appelle le service de filigrane quand la case est cochee', function () {
    $article = Article::factory()->create();

    $this->mock(WatermarkService::class, function ($mock) {
        $mock->shouldReceive('watermarkPdf')->once()->andReturn(true);
    });

    $this->actingAs($this->admin)->put(route('admin.articles.update', $article), [
        'title' => $article->title,
        'content' => $article->content,
        'pdfs' => [UploadedFile::fake()->create('rapport.pdf', 100, 'application/pdf')],
        'apply_watermark_pdfs' => '1',
    ]);
});

it('n\'appelle pas le service de filigrane quand la case n\'est pas cochee', function () {
    $article = Article::factory()->create();

    $this->mock(WatermarkService::class, function ($mock) {
        $mock->shouldReceive('watermarkPdf')->never();
    });

    $this->actingAs($this->admin)->put(route('admin.articles.update', $article), [
        'title' => $article->title,
        'content' => $article->content,
        'pdfs' => [UploadedFile::fake()->create('rapport.pdf', 100, 'application/pdf')],
    ]);
});

it('rejette un fichier qui n\'est pas un PDF dans le champ pdfs', function () {
    $article = Article::factory()->create();

    $response = $this->actingAs($this->admin)->put(route('admin.articles.update', $article), [
        'title' => $article->title,
        'content' => $article->content,
        'pdfs' => [UploadedFile::fake()->image('pas-un-pdf.jpg')],
    ]);

    $response->assertSessionHasErrors('pdfs.0');
});

it('genere la miniature PDF cote serveur, distincte de la miniature client pdf.js', function () {
    $article = Article::factory()->create();

    // Contenu PDF minimal mais reellement valide (parsable par Poppler),
    // contrairement a UploadedFile::fake()->create() qui produit des octets
    // factices sans structure PDF exploitable.
    $minimalPdf = "%PDF-1.4\n1 0 obj<</Type/Catalog/Pages 2 0 R>>endobj\n"
        ."2 0 obj<</Type/Pages/Kids[3 0 R]/Count 1>>endobj\n"
        ."3 0 obj<</Type/Page/Parent 2 0 R/MediaBox[0 0 200 200]>>endobj\n"
        ."xref\n0 4\n0000000000 65535 f \n"
        ."trailer<</Size 4/Root 1 0 R>>\nstartxref\n0\n%%EOF";

    $pdf = UploadedFile::fake()->createWithContent('rapport.pdf', $minimalPdf);

    $response = $this->actingAs($this->admin)->put(route('admin.articles.update', $article), [
        'title' => $article->title,
        'content' => $article->content,
        'pdfs' => [$pdf],
        'apply_watermark_pdfs' => '0',
    ]);

    $response->assertSessionHasNoErrors();

    $media = $article->fresh()->media()->where('mime_type', 'application/pdf')->first();

    expect($media)->not->toBeNull();
    expect($media->thumbnail_path)->not->toBeNull();
    // Signature du generateur serveur (nom base sur le fichier source, .jpg)
    // plutot que celle de l'ancienne miniature client pdf.js (prefixe "pdf_", .jpeg/.png).
    expect($media->thumbnail_path)->toEndWith('.jpg');
    expect($media->thumbnail_path)->not->toContain('pdf_');
    Storage::disk('public')->assertExists($media->thumbnail_path);
});