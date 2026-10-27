<?php

use App\Models\Article;
use App\Models\ContactMessage;
use App\Models\Page;

it('compte les messages non lus pour le Super Admin', function () {
    foreach ([null, null, now()] as $readAt) {
        (new ContactMessage(['name' => 'Awa', 'email' => 'awa@example.test', 'message' => 'Bonjour']))
            ->forceFill(['ip_hash' => str_repeat('a', 64), 'read_at' => $readAt])->save();
    }
    $this->actingAs(adminUser())->get('/admin')->assertOk()->assertSee('data-todo="messages" data-count="2"', false);
});

it('cache les messages a Publication', function () {
    (new ContactMessage(['name' => 'Awa', 'email' => 'awa@example.test', 'message' => 'Bonjour']))
        ->forceFill(['ip_hash' => str_repeat('a', 64)])->save();
    $this->actingAs(publicationUser())->get('/admin')->assertOk()
        ->assertSee('id="a-traiter"', false)
        ->assertDontSee('data-todo="messages"', false);
});

it('compte les brouillons et les pages programmees', function () {
    $user = publicationUser();
    Article::factory()->count(2)->create(['is_published' => false]);
    Page::factory()->create(['is_published' => false, 'user_id' => $user->id]);
    $scheduled = Page::factory()->create(['is_published' => true, 'published_at' => now()->addDay(), 'user_id' => $user->id]);
    expect($scheduled->status)->toBe('scheduled');
    $this->actingAs($user)->get('/admin')->assertOk()
        ->assertSee('data-todo="drafts" data-count="3"', false)
        ->assertSee('data-todo="scheduled" data-count="1"', false);
});
