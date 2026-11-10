<?php

use App\Models\ContactAttachment;
use App\Models\ContactMessage;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Storage;

function deletionMessage(int $ageDays = 0, ?string $file = null): ContactMessage
{
    $message = ContactMessage::create(['name' => 'Jean', 'email' => 'jean@example.com', 'subject' => 'Sujet', 'message' => 'Un message assez long.']);

    if ($file !== null) {
        Storage::disk('local')->put($file, 'octets');

        $attachment = new ContactAttachment(['path' => $file, 'original_name' => 'x.jpg', 'mime' => 'image/jpeg', 'size' => 6]);
        $attachment->contact_message_id = $message->id;
        $attachment->save();
    }

    if ($ageDays > 0) {
        $message->forceFill(['created_at' => now()->subDays($ageDays)])->save();
    }

    return $message;
}

it('permet au Super Admin de supprimer un message, ses images et leurs fichiers', function () {
    Storage::fake('local');
    $message = deletionMessage(0, 'contact/a.jpg');

    $this->actingAs(adminUser())->delete(route('admin.messages.destroy', $message))
        ->assertRedirect(route('admin.messages.index'))
        ->assertSessionHas('success');

    expect(ContactMessage::count())->toBe(0)->and(ContactAttachment::count())->toBe(0);
    Storage::disk('local')->assertMissing('contact/a.jpg');
});

it('interdit la suppression a un utilisateur Publication', function () {
    Storage::fake('local');
    $message = deletionMessage(0, 'contact/a.jpg');

    $this->actingAs(publicationUser())->delete(route('admin.messages.destroy', $message))->assertForbidden();

    expect(ContactMessage::count())->toBe(1);
    Storage::disk('local')->assertExists('contact/a.jpg');
});

it('renvoie un visiteur non connecte vers la connexion', function () {
    $message = deletionMessage();

    $this->delete(route('admin.messages.destroy', $message))->assertRedirect(route('login'));

    expect(ContactMessage::count())->toBe(1);
});

it('renvoie 404 pour un message inexistant', function () {
    $this->actingAs(adminUser())->delete(route('admin.messages.destroy', 999))->assertNotFound();
});

it('propose la suppression dans la liste et dans la page d un message', function () {
    $message = deletionMessage();

    $this->actingAs(adminUser())->get(route('admin.messages.index'))
        ->assertOk()->assertSee('Supprimer')->assertSee('value="DELETE"', false);

    $this->actingAs(adminUser())->get(route('admin.messages.show', $message))
        ->assertOk()->assertSee('Supprimer')->assertSee('value="DELETE"', false);
});

it('affiche la confirmation apres une suppression', function () {
    $message = deletionMessage();

    $this->actingAs(adminUser())->followingRedirects()->delete(route('admin.messages.destroy', $message))
        ->assertOk()->assertSee('Message supprim');
});

it('purge les messages anciens avec leurs fichiers et garde les recents', function () {
    Storage::fake('local');
    config(['contact.message_retention_days' => 30]);
    deletionMessage(40, 'contact/old.jpg');
    $recent = deletionMessage(5, 'contact/new.jpg');

    $this->artisan('contact:prune-messages')->assertSuccessful();

    Storage::disk('local')->assertMissing('contact/old.jpg');
    Storage::disk('local')->assertExists('contact/new.jpg');
    expect(ContactMessage::pluck('id')->all())->toBe([$recent->id])->and(ContactAttachment::count())->toBe(1);
});

it('ne purge aucun message quand la duree de conservation est zero', function () {
    Storage::fake('local');
    config(['contact.message_retention_days' => 0]);
    deletionMessage(900, 'contact/old.jpg');

    $this->artisan('contact:prune-messages')->assertSuccessful();

    Storage::disk('local')->assertExists('contact/old.jpg');
    expect(ContactMessage::count())->toBe(1);
});

it('accepte une duree donnee en option pour la purge des messages', function () {
    config(['contact.message_retention_days' => 0]);
    deletionMessage(10);

    $this->artisan('contact:prune-messages', ['--days' => 5])->assertSuccessful();

    expect(ContactMessage::count())->toBe(0);
});

it('conserve les messages un an par defaut', function () {
    expect(config('contact.message_retention_days'))->toBe(365);
});

it('planifie la purge des messages chaque jour', function () {
    Artisan::call('schedule:list');

    expect(Artisan::output())->toContain('contact:prune-messages');
});
