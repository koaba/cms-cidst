<?php

use App\Models\Page;

it('conserve data.design quand l editeur classique modifie un bloc texte', function () {
    $page = Page::factory()->create();
    $block = $page->blocks()->create([
        'type' => 'texte',
        'data' => ['title' => 'T', 'content' => 'avant', 'design' => ['align' => 'center']],
        'order' => 0,
    ]);

    $this->actingAs(adminUser())
        ->put(route('admin.pages.blocks.update', [$page, $block->id]), ['content' => 'apres'])
        ->assertRedirect();

    $data = $block->fresh()->data;

    expect($data['content'])->toBe('apres')
        ->and($data['design'] ?? null)->toBe(['align' => 'center']);
});
