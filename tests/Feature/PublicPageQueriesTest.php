<?php

use App\Models\Media;
use App\Models\Page;
use App\Models\PageBlock;
use App\Models\PdfCategory;
use App\Models\PdfDocument;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;

uses(RefreshDatabase::class);

beforeEach(function () {
    Queue::fake();
});

function addBlock(Page $page, string $type, array $data, ?PageBlock $parent = null, int $slot = 0, int $order = 0): PageBlock
{
    return $page->blocks()->create([
        'type' => $type,
        'data' => $data,
        'parent_id' => $parent?->id,
        'slot_index' => $slot,
        'order' => $order,
    ]);
}

function addImageMedia(PageBlock $block, int $count = 1): void
{
    foreach (range(1, $count) as $i) {
        $block->media()->attach(Media::factory()->create()->id, ['order' => $i, 'alt' => "alt $i"]);
    }
}

/** Une page qui exerce chaque type de bloc a relations ; $sets multiplie le contenu. */
function pageWithBlockSets(int $sets): Page
{
    $page = Page::factory()->create(['content' => 'Contenu de repli']);
    $category = PdfCategory::create(['name' => 'Categorie']);
    $order = 0;

    foreach (range(1, $sets) as $i) {
        addBlock($page, 'texte', ['title' => "Texte $i", 'content' => "Corps du texte $i"], order: $order++);
        addImageMedia(addBlock($page, 'image', ['alt' => "Image $i"], order: $order++));
        addImageMedia(addBlock($page, 'galerie', ['title' => "Galerie $i"], order: $order++), 3);

        $columns = addBlock($page, 'colonnes', ['column_count' => 2, 'title' => "Colonnes $i"], order: $order++);
        addBlock($page, 'texte', ['content' => "Colonne gauche $i"], $columns, 0);
        addImageMedia(addBlock($page, 'image', ['alt' => "Colonne image $i"], $columns, 1));

        $accordion = addBlock($page, 'accordeon', ['title' => "Accordeon $i"], order: $order++);
        $item = addBlock($page, 'accordeon_item', ['title' => "Question $i"], $accordion);
        addBlock($page, 'texte', ['content' => "Reponse $i"], $item);

        $document = PdfDocument::create(['title' => "Document $i", 'pdf_category_id' => $category->id]);
        addBlock($page, 'pdf', ['pdf_document_id' => $document->id, 'title' => "Pdf $i"], order: $order++);
    }

    return $page;
}

function pageQueryCount(Page $page): int
{
    // Echauffement : la 1re requete cree les reglages du site par defaut.
    test()->get(route('pages.show', $page))->assertOk();

    $count = 0;
    DB::listen(function () use (&$count) {
        $count++;
    });

    test()->get(route('pages.show', $page))->assertOk();

    return $count;
}

it('affiche tous les blocs de la page publique', function () {
    $page = pageWithBlockSets(1);

    $this->get(route('pages.show', $page))->assertOk()
        ->assertSee('Corps du texte 1')
        ->assertSee('Colonne gauche 1')
        ->assertSee('Question 1')
        ->assertSee('Reponse 1')
        ->assertSee('Pdf 1');
});

it('le nombre de requetes d une page ne depend pas du nombre de blocs', function () {
    $small = pageQueryCount(pageWithBlockSets(1));
    $large = pageQueryCount(pageWithBlockSets(4));

    expect($large)->toBe($small);
});

it('une page a blocs reste sous un plafond de requetes', function () {
    expect(pageQueryCount(pageWithBlockSets(3)))->toBeLessThanOrEqual(14);
});
