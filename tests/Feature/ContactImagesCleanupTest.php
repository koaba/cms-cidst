<?php

use App\Models\ContactAttachment;
use App\Models\ContactMessage;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Storage;

function cleanupAttachment(string $path = 'contact/x.jpg', int $ageDays = 0, ?ContactMessage $message = null): ContactAttachment
{
    Storage::disk('local')->put($path, 'octets');

    $message ??= ContactMessage::create(['name' => 'Jean', 'email' => 'jean@example.com', 'subject' => 'Photo', 'message' => 'Un message assez long.']);

    $attachment = new ContactAttachment(['path' => $path, 'original_name' => 'x.jpg', 'mime' => 'image/jpeg', 'size' => 6]);
    $attachment->contact_message_id = $message->id;
    $attachment->save();

    if ($ageDays > 0) {
        $attachment->forceFill(['created_at' => now()->subDays($ageDays)])->save();
    }

    return $attachment;
}

it('supprime le fichier du disque quand une image est supprimee', function () {
    Storage::fake('local');
    $attachment = cleanupAttachment();

    $attachment->delete();

    Storage::disk('local')->assertMissing('contact/x.jpg');
});

it('ne plante pas quand le fichier a deja disparu', function () {
    Storage::fake('local');
    $attachment = cleanupAttachment();
    Storage::disk('local')->delete('contact/x.jpg');

    $attachment->delete();

    expect(ContactAttachment::count())->toBe(0);
});

it('supprime les fichiers quand un message est supprime', function () {
    Storage::fake('local');
    $first = cleanupAttachment('contact/a.jpg');
    cleanupAttachment('contact/b.jpg', 0, ContactMessage::find($first->contact_message_id));

    ContactMessage::find($first->contact_message_id)->delete();

    Storage::disk('local')->assertMissing('contact/a.jpg');
    Storage::disk('local')->assertMissing('contact/b.jpg');
    expect(ContactAttachment::count())->toBe(0);
});

it('purge les images anciennes et garde les recentes et les messages', function () {
    Storage::fake('local');
    config(['contact.retention_days' => 30]);
    $old = cleanupAttachment('contact/old.jpg', 40);
    cleanupAttachment('contact/new.jpg', 5);

    $this->artisan('contact:prune-images')->assertSuccessful();

    Storage::disk('local')->assertMissing('contact/old.jpg');
    Storage::disk('local')->assertExists('contact/new.jpg');
    expect(ContactAttachment::count())->toBe(1)
        ->and(ContactMessage::find($old->contact_message_id))->not->toBeNull();
});

it('ne purge rien quand la duree de conservation est zero', function () {
    Storage::fake('local');
    config(['contact.retention_days' => 0]);
    cleanupAttachment('contact/old.jpg', 400);

    $this->artisan('contact:prune-images')->assertSuccessful();

    Storage::disk('local')->assertExists('contact/old.jpg');
    expect(ContactAttachment::count())->toBe(1);
});

it('accepte une duree donnee en option', function () {
    Storage::fake('local');
    config(['contact.retention_days' => 0]);
    cleanupAttachment('contact/old.jpg', 10);

    $this->artisan('contact:prune-images', ['--days' => 5])->assertSuccessful();

    expect(ContactAttachment::count())->toBe(0);
});

it('planifie la purge chaque jour', function () {
    Artisan::call('schedule:list');

    expect(Artisan::output())->toContain('contact:prune-images');
});
