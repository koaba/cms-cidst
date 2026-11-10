<?php

use App\Models\NewsTicker;
use App\Models\Page;
use App\Models\Slider;
use App\Support\SafeUrl;

it('accepte les liens sans danger', function (string $url) {
    expect(SafeUrl::isSafe($url))->toBeTrue();
})->with([
    'chemin du site' => ['/contact'],
    'chemin avec requete et ancre' => ['/blog?page=2#haut'],
    'ancre' => ['#haut'],
    'chemin relatif' => ['contact'],
    'https' => ['https://example.com/page'],
    'http' => ['http://example.com'],
    'schema en majuscules' => ['HTTPS://EXAMPLE.COM'],
    'courriel' => ['mailto:contact@example.com'],
    'telephone' => ['tel:+261340000000'],
]);

it('refuse les liens dangereux', function (string $url) {
    expect(SafeUrl::isSafe($url))->toBeFalse();
})->with([
    'javascript' => ['javascript:alert(1)'],
    'javascript en casse mixte' => ['JaVaScRiPt:alert(1)'],
    'javascript precede d un espace' => [' javascript:alert(1)'],
    'javascript coupe par une tabulation' => ["java\tscript:alert(1)"],
    'javascript coupe par un saut de ligne' => ["java\nscript:alert(1)"],
    'data' => ['data:text/html,<script>alert(1)</script>'],
    'vbscript' => ['vbscript:msgbox(1)'],
    'hote sans schema' => ['//evil.example'],
    'hote avec antislash' => ['/\\evil.example'],
    'hote avec deux antislashs' => ['\\\\evil.example'],
    'http sans slashs' => ['http:evil.example'],
    'http sans hote' => ['http://'],
    'courriel vide' => ['mailto:'],
    'ftp' => ['ftp://example.com'],
    'file' => ['file:///etc/passwd'],
]);

it('remplace un lien dangereux par une ancre et garde un lien sain', function () {
    expect(SafeUrl::sanitize('javascript:alert(1)'))->toBe('#')
        ->and(SafeUrl::sanitize(null))->toBe('#')
        ->and(SafeUrl::sanitize('  /contact '))->toBe('/contact');
});

it('refuse un schema dangereux dans un bloc bouton', function () {
    $page = Page::factory()->create();

    $this->actingAs(adminUser())
        ->post(route('admin.pages.blocks.store', $page), ['type' => 'bouton', 'label' => 'Go', 'url' => 'javascript:alert(1)'])
        ->assertSessionHasErrors('url');

    expect($page->blocks()->count())->toBe(0);
});

it('accepte les liens courants dans un bloc bouton', function () {
    $admin = adminUser();

    foreach (['https://example.com', 'http://example.com', '/contact', '#haut', 'mailto:contact@example.com', 'tel:+261340000000'] as $url) {
        $page = Page::factory()->create();

        $this->actingAs($admin)
            ->post(route('admin.pages.blocks.store', $page), ['type' => 'bouton', 'label' => 'Go', 'url' => $url])
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        expect($page->blocks()->first()?->data['url'])->toBe($url);
    }
});

it('refuse un schema dangereux dans un bloc section a fond colore', function () {
    $page = Page::factory()->create();

    $this->actingAs(adminUser())
        ->post(route('admin.pages.blocks.store', $page), ['type' => 'section_fond', 'text' => 'Texte', 'button_label' => 'Go', 'button_url' => 'javascript:alert(1)'])
        ->assertSessionHasErrors('button_url');

    expect($page->blocks()->count())->toBe(0);
});

it('refuse un schema dangereux dans une actualite', function () {
    $admin = adminUser();

    $this->actingAs($admin)
        ->post(route('admin.news-tickers.store'), ['content' => 'Info', 'link_url' => 'javascript:alert(1)'])
        ->assertSessionHasErrors('link_url');

    $ticker = NewsTicker::factory()->create(['content' => 'Avant', 'link_url' => '/ok']);

    $this->actingAs($admin)
        ->put(route('admin.news-tickers.update', $ticker), ['content' => 'Apres', 'link_url' => 'javascript:alert(1)'])
        ->assertSessionHasErrors('link_url');

    expect(NewsTicker::count())->toBe(1)->and($ticker->fresh()->link_url)->toBe('/ok');
});

it('refuse un schema dangereux dans un slider', function () {
    $this->actingAs(adminUser())
        ->post(route('admin.sliders.store'), ['title' => 'Slider', 'link_url' => 'javascript:alert(1)'])
        ->assertSessionHasErrors('link_url');

    expect(Slider::count())->toBe(0);
});

it('neutralise un lien dangereux deja enregistre dans un bouton', function () {
    $dangerous = view('pages.blocks._bouton', ['data' => ['label' => 'Go', 'url' => 'javascript:alert(1)']])->render();
    $safe = view('pages.blocks._bouton', ['data' => ['label' => 'Go', 'url' => '/contact']])->render();

    expect($dangerous)->not->toContain('javascript:')->toContain('href="#"')
        ->and($safe)->toContain('href="/contact"');
});

it('neutralise un lien dangereux deja enregistre dans une section a fond colore', function () {
    $data = ['text' => 'Texte', 'button_label' => 'Go', 'button_url' => 'javascript:alert(1)'];

    $dangerous = view('pages.blocks._section_fond', ['data' => $data])->render();
    $safe = view('pages.blocks._section_fond', ['data' => ['button_url' => '/contact'] + $data])->render();

    expect($dangerous)->not->toContain('javascript:')->toContain('href="#"')
        ->and($safe)->toContain('href="/contact"');
});

it('neutralise un lien dangereux deja enregistre dans une banniere hero', function () {
    $data = ['titre' => 'Titre', 'bouton_texte' => 'Go', 'bouton_url' => 'javascript:alert(1)'];

    $dangerous = view('pages.blocks._banniere_hero', ['media' => collect(), 'data' => $data])->render();
    $safe = view('pages.blocks._banniere_hero', ['media' => collect(), 'data' => ['bouton_url' => 'https://example.com'] + $data])->render();

    expect($dangerous)->not->toContain('javascript:')->toContain('href="#"')
        ->and($safe)->toContain('href="https://example.com"');
});

it('neutralise un lien dangereux deja enregistre dans le bandeau d actualites', function () {
    $items = collect([(object) ['content' => 'Info', 'link_url' => 'javascript:alert(1)']]);

    foreach (['horizontal', 'vertical'] as $direction) {
        $html = view('components.news-ticker', ['items' => $items, 'direction' => $direction])->render();

        expect($html)->not->toContain('javascript:')->toContain('href="#"');
    }

    $safe = view('components.news-ticker', ['items' => collect([(object) ['content' => 'Info', 'link_url' => '/pages']]), 'direction' => 'horizontal'])->render();

    expect($safe)->toContain('href="/pages"');
});

it('neutralise un lien dangereux deja enregistre dans un slider', function () {
    Slider::factory()->create(['title' => 'Dangereux', 'link_url' => 'javascript:alert(1)']);
    Slider::factory()->create(['title' => 'Sain', 'link_url' => '/blog/mon-article']);

    foreach ([route('sliders.index'), route('home')] as $url) {
        $this->get($url)
            ->assertOk()
            ->assertDontSee('javascript:alert(1)', false)
            ->assertSee('href="/blog/mon-article"', false);
    }
});
