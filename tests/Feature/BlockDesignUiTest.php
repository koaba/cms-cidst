<?php

use App\Blocks\BlockRegistry;
use App\Models\Page;
use App\Models\User;
use App\Services\PageBuilder\BlockFieldCatalog;
use App\Services\PageBuilder\BlockSyncService;
use Illuminate\Support\Facades\Validator;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    Role::firstOrCreate(['name' => 'Super Admin']);
    $this->admin = User::factory()->create();
    $this->admin->assignRole('Super Admin');
    $this->page = Page::factory()->create();
});

it('decrit le selecteur d alignement du bloc texte hors de fields et defaults', function () {
    $definition = BlockFieldCatalog::for('texte');
    $align = $definition['design']['fields'][0];

    expect($definition['design']['fields'])->toHaveCount(1)
        ->and($align['key'])->toBe('align')
        ->and($align['type'])->toBe('select')
        ->and(array_column($align['options'], 'value'))->toBe(['', 'left', 'center', 'right'])
        ->and(array_column($definition['fields'], 'key'))->not->toContain('design')
        ->and($definition['defaults'])->not->toHaveKey('design');
});

it('decrit pour chaque type editable les memes options design que ses regles', function (string $type) {
    $ruleKeys = collect(array_keys(BlockRegistry::rulesFor($type)->rules(false)))
        ->filter(fn (string $key) => str_starts_with($key, 'design.'))
        ->map(fn (string $key) => substr($key, strlen('design.')))
        ->values()
        ->all();

    $described = array_column(BlockFieldCatalog::for($type)['design']['fields'] ?? [], 'key');

    expect($described)->toEqualCanonicalizing($ruleKeys);
})->with(fn () => BlockSyncService::editableTypes());

it('ne propose que des alignements acceptes par les regles du bloc texte', function () {
    $rules = BlockRegistry::rulesFor('texte')->rules(false);
    $options = BlockFieldCatalog::for('texte')['design']['fields'][0]['options'];

    foreach (array_filter(array_column($options, 'value')) as $value) {
        $validator = Validator::make(['content' => 'x', 'design' => ['align' => $value]], $rules);

        expect($validator->passes())->toBeTrue();
    }
});

it('expose au builder la description du design et la valeur enregistree', function () {
    $this->page->blocks()->create([
        'type' => 'texte',
        'data' => ['content' => 'x', 'design' => ['align' => 'center']],
        'order' => 0,
    ]);

    $this->actingAs($this->admin)
        ->get(route('admin.pages.builder.show', $this->page))
        ->assertOk()
        ->assertViewHas('builder', function (array $builder) {
            expect($builder['types']['texte']['design']['fields'][0]['key'])->toBe('align')
                ->and($builder['blocks'][0]['data']['design']['align'])->toBe('center');

            return true;
        });
});

it('branche le fieldset Apparence dans la vue et le composant du builder', function () {
    $this->actingAs($this->admin)
        ->get(route('admin.pages.builder.show', $this->page))
        ->assertOk()
        ->assertSee('Apparence', false)
        ->assertSee('designFields', false)
        ->assertSee('designValue(', false)
        ->assertSee('setDesign(', false);

    expect(file_get_contents(resource_path('js/admin/page-builder.js')))
        ->toContain('export function applyDesign')
        ->toContain('designFields')
        ->toContain('setDesign(');
});
