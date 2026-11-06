<?php

use App\Models\Page;

it('affiche la bulle d aide du slug sur les formulaires de page', function () {
    foreach ([adminUser(), publicationUser()] as $user) {
        $page = Page::factory()->create(['user_id' => $user->id]);
        foreach ([route('admin.pages.create'), route('admin.pages.edit', $page)] as $url) {
            $this->actingAs($user)->get($url)->assertOk()
                ->assertSee('data-help="slug"', false)
                ->assertSee(route('admin.guide').'#guide-glossaire', false);
        }
    }
});
