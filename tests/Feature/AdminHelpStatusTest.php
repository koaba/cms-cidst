<?php

it('affiche la bulle d aide du statut dans les listes', function () {
    foreach ([adminUser(), publicationUser()] as $user) {
        foreach ([route('admin.pages.index'), route('admin.articles.index')] as $url) {
            $this->actingAs($user)->get($url)->assertOk()->assertSee('data-help="statut"', false);
        }
    }
});
