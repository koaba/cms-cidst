<?php

use App\Models\Media;

it('rend la zone d erreur du glisser-deposer, masquee par defaut', function () {
    $media = Media::factory()->create();

    $this->blade(
        '<x-admin.media-reorder-list :items="$items" model-type="article" :model-id="7" delete-field="delete_images" />',
        ['items' => collect([$media])],
    )
        ->assertSee('<p class="hidden w-full text-sm text-error" role="alert" data-reorder-status></p>', false)
        ->assertSee('data-media-id="'.$media->id.'"', false);
});
