<?php

use App\Models\ContactMessage;

function unreadMessage(): ContactMessage
{
    $m = new ContactMessage(['name' => 'Awa', 'email' => 'awa@example.test', 'subject' => 'Bonjour', 'message' => 'Un long message de test.']);
    $m->ip_hash = str_repeat('a', 64);
    $m->save();

    return $m;
}

it('liste les messages pour le Super Admin', function () {
    unreadMessage();
    $this->actingAs(adminUser())->get('/admin/messages')->assertOk()->assertSee('Awa')->assertSee('Bonjour');
});

it('refuse la liste a Publication', function () {
    $this->actingAs(publicationUser())->get('/admin/messages')->assertForbidden();
});

it('redirige un visiteur non connecte', function () {
    $this->get('/admin/messages')->assertRedirect();
});

it('marque le message comme lu et propose Repondre', function () {
    $m = unreadMessage();
    $this->actingAs(adminUser())->get('/admin/messages/'.$m->id)->assertOk()->assertSee('mailto:awa@example.test', false);
    expect($m->fresh()->read_at)->not->toBeNull();
});

it('refuse la lecture a Publication sans la marquer lue', function () {
    $m = unreadMessage();
    $this->actingAs(publicationUser())->get('/admin/messages/'.$m->id)->assertForbidden();
    expect($m->fresh()->read_at)->toBeNull();
});

it('affiche l entree Messages dans la sidebar du Super Admin seulement', function () {
    $this->actingAs(adminUser())->get('/admin')->assertOk()->assertSee(route('admin.messages.index'), false);
    $this->actingAs(publicationUser())->get('/admin')->assertOk()->assertDontSee(route('admin.messages.index'), false);
});
