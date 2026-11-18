<?php

it('fournit un favicon valide et leger (logo CIDST)', function () {
    $path = public_path('favicon.ico');
    $bytes = file_get_contents($path);

    expect(strlen($bytes))->toBeGreaterThan(100)->toBeLessThan(2048)
        ->and(substr($bytes, 0, 4))->toBe("\x00\x00\x01\x00");
});

it('declare le meme favicon sur le site public', function () {
    $this->get('/')->assertOk()->assertSee('rel="icon" href="'.asset('favicon.ico').'?v=', false);
    $this->get('/contact')->assertOk()->assertSee('rel="icon" href="'.asset('favicon.ico').'?v=', false);
    $this->get('/blog')->assertOk()->assertSee('rel="icon" href="'.asset('favicon.ico').'?v=', false);
});

it('declare le favicon sur la connexion, l administration et le profil', function () {
    $this->get('/login')->assertOk()->assertSee('rel="icon" href="'.asset('favicon.ico').'?v=', false);
    $this->actingAs(adminUser())->get('/admin')->assertOk()->assertSee('rel="icon" href="'.asset('favicon.ico').'?v=', false);
    $this->actingAs(adminUser())->get('/profile')->assertOk()->assertSee('rel="icon" href="'.asset('favicon.ico').'?v=', false);
});
