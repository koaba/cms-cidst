<?php

use App\Mail\ContactAlert;
use Illuminate\Support\Facades\Mail;

it('annonce un nouveau message avec le bon sujet', function () {
    expect((new ContactAlert)->build()->subject)->toBe('Nouveau message de contact');
});

it('rend une version HTML qui renvoie vers l administration', function () {
    $html = (new ContactAlert)->render();

    expect($html)->toContain('Nouveau message de contact')
        ->and($html)->toContain("Un nouveau message vous attend dans l'administration.");
});

it('rend une version texte qui renvoie vers l administration', function () {
    $texte = view('emails.contact-alert-text')->render();

    expect($texte)->toContain("un message vous attend dans l'administration.");
});

it('envoie l alerte a l adresse expeditrice sans aucune donnee du visiteur', function () {
    config(['mail.from.address' => 'alerte@cidst.test']);
    Mail::fake();

    $this->post('/contact', [
        'name' => 'Zorglub Unique',
        'email' => 'zorglub@visiteur.test',
        'subject' => 'Sujet Confidentiel',
        'message' => 'Corps du message confidentiel.',
        'website' => '',
    ])->assertRedirect()->assertSessionHasNoErrors();

    Mail::assertSent(ContactAlert::class, function (ContactAlert $mail) {
        $contenu = $mail->render();

        foreach (['Zorglub', 'zorglub@visiteur.test', 'Sujet Confidentiel', 'Corps du message'] as $secret) {
            expect($contenu)->not->toContain($secret);
        }

        return $mail->hasTo('alerte@cidst.test');
    });
});
