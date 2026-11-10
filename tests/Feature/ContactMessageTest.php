<?php

use App\Models\ContactMessage;
use Illuminate\Support\Defer\DeferredCallbackCollection;
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

it('affiche le formulaire de contact', function () {
    $this->get('/contact')->assertOk()->assertSee('name="website"', false)->assertSee('Contact');
});

it('renseigne alerted_at quand l alerte part', function () {
    Mail::fake();
    $this->post('/contact', contactPayload())->assertRedirect();

    expect(ContactMessage::first()->alerted_at)->not->toBeNull();
});

it('laisse alerted_at vide quand l alerte echoue', function () {
    Mail::shouldReceive('to')->andThrow(new RuntimeException('smtp'));
    $this->post('/contact', contactPayload())->assertRedirect();

    expect(ContactMessage::first()->alerted_at)->toBeNull();
});

it('envoie l alerte apres la reponse et non pendant la requete', function () {
    Mail::fake();
    $deferred = new class extends DeferredCallbackCollection
    {
        public function invokeWhen(?Closure $when = null): void {}

        public function flush(): void
        {
            parent::invokeWhen();
        }
    };
    app()->instance(DeferredCallbackCollection::class, $deferred);

    $this->post('/contact', contactPayload())->assertRedirect();

    Mail::assertNothingSent();
    expect(ContactMessage::first()->alerted_at)->toBeNull();

    $deferred->flush();

    Mail::assertSentCount(1);
    expect(ContactMessage::first()->alerted_at)->not->toBeNull();
});

it('enregistre un lien de document https facultatif', function () {
    Mail::fake();
    $link = 'https://drive.google.com/file/d/abc/view';

    $this->post('/contact', contactPayload(['attachment_link' => $link]))->assertRedirect()->assertSessionHasNoErrors();

    expect(ContactMessage::first()->attachment_link)->toBe($link);
});

it('refuse un lien qui n est pas en https', function (string $link) {
    $this->post('/contact', contactPayload(['attachment_link' => $link]))->assertSessionHasErrors('attachment_link');

    expect(ContactMessage::count())->toBe(0);
})->with([
    'http' => 'http://example.com/f.pdf',
    'javascript' => 'javascript:alert(1)',
    'ftp' => 'ftp://example.com/f.pdf',
    'file' => 'file:///etc/passwd',
    'texte' => 'pas un lien',
]);

it('refuse un lien de plus de 500 caracteres', function () {
    $this->post('/contact', contactPayload(['attachment_link' => 'https://example.com/'.str_repeat('a', 500)]))
        ->assertSessionHasErrors('attachment_link');
});

it('affiche la consigne de partage sur le formulaire', function () {
    $this->get('/contact')
        ->assertOk()
        ->assertSee('name="attachment_link"', false)
        ->assertSee('Google Drive', false);
});

it('affiche le lien dans l administration avec un rel securise', function () {
    $m = ContactMessage::create([
        'name' => 'Jean', 'email' => 'jean@example.com', 'subject' => 'Doc', 'message' => 'Un message assez long.',
        'attachment_link' => 'https://drive.google.com/x',
    ]);

    $this->actingAs(adminUser())->get(route('admin.messages.show', $m))
        ->assertOk()
        ->assertSee('https://drive.google.com/x', false)
        ->assertSee('rel="noopener noreferrer nofollow"', false);
});
