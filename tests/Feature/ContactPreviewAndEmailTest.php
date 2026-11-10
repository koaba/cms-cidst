<?php

use App\Models\ContactMessage;
use App\Models\SiteSetting;
use Illuminate\Support\Facades\Storage;

function previewMessage(string $name = 'Jean'): ContactMessage
{
    return ContactMessage::create(['name' => $name, 'email' => 'jean@example.com', 'message' => 'Un message assez long.']);
}

function previewSettingsPayload(array $extra = []): array
{
    return array_merge(['hero_title' => 'Titre'], $extra);
}

it('affiche une image en apercu dans le navigateur d administration', function () {
    Storage::fake('local');
    $m = previewMessage();
    Storage::disk('local')->put('contact/abc.jpg', 'contenu-image');
    $a = $m->attachments()->create(['path' => 'contact/abc.jpg', 'original_name' => 'photo.jpg', 'mime' => 'image/jpeg', 'size' => 13]);

    $response = $this->actingAs(adminUser())->get(route('admin.messages.attachments.preview', [$m, $a]));

    $response->assertOk()->assertHeader('X-Content-Type-Options', 'nosniff')->assertHeader('Content-Type', 'image/jpeg');
    expect($response->headers->get('Content-Disposition'))->toStartWith('inline')
        ->and($response->streamedContent())->toBe('contenu-image');
});

it('interdit l apercu a un utilisateur Publication', function () {
    Storage::fake('local');
    $m = previewMessage();
    $a = $m->attachments()->create(['path' => 'contact/abc.jpg', 'original_name' => 'photo.jpg', 'mime' => 'image/jpeg', 'size' => 13]);

    $this->actingAs(publicationUser())->get(route('admin.messages.attachments.preview', [$m, $a]))->assertForbidden();
});

it('refuse l apercu d une image rattachee a un autre message', function () {
    Storage::fake('local');
    $m1 = previewMessage();
    $m2 = previewMessage('Paul');
    Storage::disk('local')->put('contact/abc.jpg', 'x');
    $a = $m2->attachments()->create(['path' => 'contact/abc.jpg', 'original_name' => 'photo.jpg', 'mime' => 'image/jpeg', 'size' => 1]);

    $this->actingAs(adminUser())->get(route('admin.messages.attachments.preview', [$m1, $a]))->assertNotFound();
});

it('refuse l apercu quand le fichier a disparu', function () {
    Storage::fake('local');
    $m = previewMessage();
    $a = $m->attachments()->create(['path' => 'contact/absent.jpg', 'original_name' => 'photo.jpg', 'mime' => 'image/jpeg', 'size' => 1]);

    $this->actingAs(adminUser())->get(route('admin.messages.attachments.preview', [$m, $a]))->assertNotFound();
});

it('affiche une miniature de chaque image dans la page du message', function () {
    $m = previewMessage();
    $a = $m->attachments()->create(['path' => 'contact/abc.jpg', 'original_name' => 'photo.jpg', 'mime' => 'image/jpeg', 'size' => 2048]);

    $this->actingAs(adminUser())->get(route('admin.messages.show', $m))
        ->assertOk()
        ->assertSee('<img src="'.route('admin.messages.attachments.preview', [$m, $a]).'"', false);
});

it('affiche l adresse e-mail publique sur la page contact', function () {
    SiteSetting::current()->update(['contact_public_email' => 'documents@cidst.mg']);

    $this->get('/contact')->assertOk()
        ->assertSee('id="contact_public_email"', false)
        ->assertSee('mailto:documents@cidst.mg', false);
});

it('masque la phrase d adresse e-mail quand aucune adresse n est reglee', function () {
    $this->get('/contact')->assertOk()->assertDontSee('id="contact_public_email"', false);
});

it('sauvegarde l adresse e-mail publique dans les reglages', function () {
    $this->actingAs(adminUser())->put(route('admin.settings.update'), previewSettingsPayload(['contact_public_email' => 'documents@cidst.mg']))
        ->assertRedirect()->assertSessionHasNoErrors();

    expect(SiteSetting::current()->contact_public_email)->toBe('documents@cidst.mg');
});

it('refuse une adresse e-mail publique invalide', function () {
    $this->actingAs(adminUser())->put(route('admin.settings.update'), previewSettingsPayload(['contact_public_email' => 'pas-une-adresse']))
        ->assertSessionHasErrors('contact_public_email');

    expect(SiteSetting::current()->contact_public_email)->toBeNull();
});

it('efface l adresse e-mail publique quand le champ est vide', function () {
    SiteSetting::current()->update(['contact_public_email' => 'documents@cidst.mg']);

    $this->actingAs(adminUser())->put(route('admin.settings.update'), previewSettingsPayload(['contact_public_email' => '']))
        ->assertRedirect()->assertSessionHasNoErrors();

    expect(SiteSetting::current()->contact_public_email)->toBeNull();
});
