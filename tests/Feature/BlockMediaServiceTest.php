<?php

use App\Services\BlockMediaService;

function allMediaKeys(): array
{
    return [
        'image', 'delete_image',
        'video_file', 'delete_video',
        'images', 'images_alt', 'images_caption', 'delete_media',
        'pdf_source', 'pdf_document_id', 'pdf_title', 'pdfs',
        'apply_watermark',
    ];
}

it("conserve tous les champs média sur un type qui n'en déclare aucun", function (string $key) {
    $data = app(BlockMediaService::class)->stripMediaFields([$key => 'x', 'title' => 'T'], 'texte');

    expect($data)->toHaveKeys([$key, 'title']);
})->with(allMediaKeys());

it('retire uniquement les champs média déclarés par le type', function (string $type, array $declared) {
    $input = array_fill_keys(allMediaKeys(), 'x') + ['title' => 'T'];

    $data = app(BlockMediaService::class)->stripMediaFields($input, $type);

    expect(array_keys($data))->toEqualCanonicalizing(
        ['title', ...array_values(array_diff(allMediaKeys(), $declared))]
    );
})->with([
    'image' => ['image', ['image', 'delete_image', 'apply_watermark']],
    'banniere_hero' => ['banniere_hero', ['image', 'delete_image', 'apply_watermark']],
    'video' => ['video', ['video_file', 'delete_video', 'apply_watermark']],
    'galerie' => ['galerie', ['images', 'images_alt', 'images_caption', 'delete_media', 'apply_watermark']],
    'pdf' => ['pdf', ['pdf_source', 'pdf_document_id', 'pdf_title', 'pdfs', 'apply_watermark']],
]);
