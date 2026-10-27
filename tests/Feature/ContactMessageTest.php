<?php

use App\Models\ContactMessage;
use Illuminate\Support\Facades\Mail;

function contactPayload(array $o = []): array
{
    return array_merge(['name' => 'Jean', 'email' => 'jean@example.com', 'subject' => 'Bonjour', 'message' => 'Un message assez long.', 'website' => ''], $o);
}

it('enregistre un message valide avec une IP hachee', function () {
    Mail::fake();
    $this->post('/contact', contactPayload())->assertRedirect()->assertSessionHasNoErrors();
    $m = ContactMessage::first();
    expect($m)->not->toBeNull()->and($m->ip_hash)->toHaveLength(64)->not->toBe('127.0.0.1');
    Mail::assertSentCount(1);
});

it('ignore silencieusement un honeypot rempli', function () {
    $this->post('/contact', contactPayload(['website' => 'http://spam.test']))->assertRedirect();
    expect(ContactMessage::count())->toBe(0);
});

it('valide nom, email et message', function () {
    $this->post('/contact', [])->assertSessionHasErrors(['name', 'email', 'message']);
    expect(ContactMessage::count())->toBe(0);
});

it('limite a 5 envois par minute', function () {
    Mail::fake();
    foreach (range(1, 5) as $i) {
        $this->post('/contact', contactPayload());
    }
    $this->post('/contact', contactPayload())->assertStatus(429);
});

it('garde le message si l alerte echoue', function () {
    Mail::shouldReceive('to')->andThrow(new RuntimeException('smtp'));
    $this->post('/contact', contactPayload())->assertRedirect();
    expect(ContactMessage::count())->toBe(1);
});
