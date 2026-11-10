<?php

use App\Models\ContactAttachment;
use App\Models\ContactMessage;
use App\Models\SiteSetting;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;

function imagesPayload(array $o = []): array
{
    return array_merge(['name' => 'Jean', 'email' => 'jean@example.com', 'subject' => 'Photo', 'message' => 'Un message assez long.', 'website' => ''], $o);
}

function imagesOn(int $maxMb = 5): void
{
    SiteSetting::current()->update(['contact_images_enabled' => true, 'contact_images_max_mb' => $maxMb]);
}

it('masque le champ images quand la fonction est desactivee', function () {
    $this->get('/contact')->assertOk()->assertDontSee('name="images[]"', false);
});

it('affiche le champ images quand la fonction est activee', function () {
    imagesOn();

    $this->get('/contact')->assertOk()->assertSee('name="images[]"', false)->assertSee('multipart/form-data', false);
});

it('refuse les images quand la fonction est desactivee', function () {
    Storage::fake('local');

    $this->post('/contact', imagesPayload(['images' => [UploadedFile::fake()->image('a.jpg', 50, 50)]]))
        ->assertSessionHasErrors('images');

    expect(ContactMessage::count())->toBe(0);
});

it('range une image dans le disque prive sous un nom aleatoire', function () {
    Storage::fake('local');
    Mail::fake();
    imagesOn();

    $this->post('/contact', imagesPayload(['images' => [UploadedFile::fake()->image('photo.jpg', 200, 150)]]))
        ->assertRedirect()->assertSessionHasNoErrors();

    $a = ContactAttachment::first();
    expect($a)->not->toBeNull()
        ->and($a->path)->toStartWith('contact/')->not->toContain('photo')
        ->and($a->mime)->toBe('image/jpeg')
        ->and($a->original_name)->toBe('photo.jpg');
    Storage::disk('local')->assertExists($a->path);
});

it('supprime tout contenu cache ajoute apres l image', function () {
    Storage::fake('local');
    Mail::fake();
    imagesOn();

    $source = UploadedFile::fake()->image('a.jpg', 50, 50);
    $bytes = file_get_contents($source->getPathname()).'<?php echo "piege"; ?>';

    $this->post('/contact', imagesPayload(['images' => [UploadedFile::fake()->createWithContent('a.jpg', $bytes)]]))
        ->assertRedirect()->assertSessionHasNoErrors();

    $stored = Storage::disk('local')->get(ContactAttachment::firstOrFail()->path);
    expect(str_contains($stored, '<?php'))->toBeFalse()
        ->and(getimagesizefromstring($stored))->not->toBeFalse();
});

it('refuse un faux fichier image', function () {
    Storage::fake('local');
    imagesOn();

    $this->post('/contact', imagesPayload(['images' => [UploadedFile::fake()->createWithContent('x.jpg', '<?php echo 1;')]]))
        ->assertSessionHasErrors('images.0');

    expect(ContactMessage::count())->toBe(0)->and(ContactAttachment::count())->toBe(0);
});

it('refuse une image trop grande en pixels', function () {
    Storage::fake('local');
    imagesOn();

    $this->post('/contact', imagesPayload(['images' => [UploadedFile::fake()->image('big.jpg', 4001, 10)]]))
        ->assertSessionHasErrors('images.0');
});

it('refuse plus de trois images', function () {
    Storage::fake('local');
    imagesOn();

    $images = array_map(fn ($i) => UploadedFile::fake()->image("p{$i}.jpg", 20, 20), range(1, 4));

    $this->post('/contact', imagesPayload(['images' => $images]))->assertSessionHasErrors('images');
});

it('refuse une image au dela de la taille maximale reglee', function () {
    Storage::fake('local');
    imagesOn(1);

    $this->post('/contact', imagesPayload(['images' => [UploadedFile::fake()->image('lourde.jpg', 100, 100)->size(2048)]]))
        ->assertSessionHasErrors('images.0');
});

it('telecharge une image depuis l administration en piece jointe', function () {
    Storage::fake('local');
    $m = ContactMessage::create(['name' => 'Jean', 'email' => 'jean@example.com', 'message' => 'Un message assez long.']);
    Storage::disk('local')->put('contact/abc.jpg', 'contenu-image');
    $a = $m->attachments()->create(['path' => 'contact/abc.jpg', 'original_name' => 'photo.jpg', 'mime' => 'image/jpeg', 'size' => 13]);

    $response = $this->actingAs(adminUser())->get(route('admin.messages.attachments.show', [$m, $a]));

    $response->assertOk()->assertHeader('X-Content-Type-Options', 'nosniff');
    expect($response->headers->get('Content-Disposition'))->toContain('attachment')
        ->and($response->streamedContent())->toBe('contenu-image');
});

it('interdit le telechargement a un utilisateur Publication', function () {
    Storage::fake('local');
    $m = ContactMessage::create(['name' => 'Jean', 'email' => 'jean@example.com', 'message' => 'Un message assez long.']);
    $a = $m->attachments()->create(['path' => 'contact/abc.jpg', 'original_name' => 'photo.jpg', 'mime' => 'image/jpeg', 'size' => 13]);

    $this->actingAs(publicationUser())->get(route('admin.messages.attachments.show', [$m, $a]))->assertForbidden();
});

it('refuse une image rattachee a un autre message', function () {
    Storage::fake('local');
    $m1 = ContactMessage::create(['name' => 'Jean', 'email' => 'jean@example.com', 'message' => 'Un message assez long.']);
    $m2 = ContactMessage::create(['name' => 'Paul', 'email' => 'paul@example.com', 'message' => 'Un autre message long.']);
    Storage::disk('local')->put('contact/abc.jpg', 'x');
    $a = $m2->attachments()->create(['path' => 'contact/abc.jpg', 'original_name' => 'photo.jpg', 'mime' => 'image/jpeg', 'size' => 1]);

    $this->actingAs(adminUser())->get(route('admin.messages.attachments.show', [$m1, $a]))->assertNotFound();
});

it('liste les images jointes dans la page du message', function () {
    $m = ContactMessage::create(['name' => 'Jean', 'email' => 'jean@example.com', 'message' => 'Un message assez long.']);
    $a = $m->attachments()->create(['path' => 'contact/abc.jpg', 'original_name' => 'photo.jpg', 'mime' => 'image/jpeg', 'size' => 2048]);

    $this->actingAs(adminUser())->get(route('admin.messages.show', $m))
        ->assertOk()
        ->assertSee('photo.jpg')
        ->assertSee(route('admin.messages.attachments.show', [$m, $a]), false);
});

it('sauvegarde les reglages des images jointes', function () {
    $this->actingAs(adminUser())->put(route('admin.settings.update'), [
        'hero_title' => 'Titre',
        'contact_images_enabled' => '1',
        'contact_images_max_mb' => '3',
    ])->assertRedirect()->assertSessionHasNoErrors();

    $s = SiteSetting::current()->refresh();
    expect($s->contact_images_enabled)->toBeTrue()->and($s->contact_images_max_mb)->toBe(3);
});

it('refuse une taille maximale hors limites', function () {
    $this->actingAs(adminUser())->put(route('admin.settings.update'), [
        'hero_title' => 'Titre',
        'contact_images_max_mb' => '50',
    ])->assertSessionHasErrors('contact_images_max_mb');
});
