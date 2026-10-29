<?php

namespace App\Services\PageBuilder;

use App\Blocks\BlockRegistry;
use App\Contracts\DeclaresMediaFields;
use App\Models\Media;
use App\Models\Page;
use App\Models\PageBlock;
use App\Services\BlockMediaService;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

/**
 * Synchronise les blocs racine d'une page avec la liste envoyee par le
 * Page Builder : creation, modification, reordonnancement, suppression.
 *
 * Regle de securite : liste blanche. Un type absent de EDITABLE_TYPES
 * (colonnes, accordeon, galerie... et tout type futur) n'est jamais
 * modifie ni supprime ici : seul son ordre change. Rien n'est ecrit
 * tant que tous les blocs du payload ne sont pas valides.
 */
class BlockSyncService
{
    /** Types que le builder sait editer. Tous sont des blocs feuilles (sans enfants). */
    public const EDITABLE_TYPES = ['texte', 'banniere_hero', 'image', 'bouton', 'citation', 'separateur'];

    /** Types a image unique : l'image est choisie dans la mediatheque (media_id). */
    public const MEDIA_TYPES = ['banniere_hero', 'image'];

    public function __construct(private BlockMediaService $blockMedia) {}

    /**
     * @param  list<array{id?: int|null, type: string, data?: array|null}>  $items  blocs dans l'ordre voulu
     * @return list<array{id: int, type: string, order: int}> blocs racine apres sauvegarde
     */
    public function sync(Page $page, array $items, ?string $expectedVersion = null): array
    {
        return DB::transaction(function () use ($page, $items, $expectedVersion) {
            Page::query()->whereKey($page->getKey())->lockForUpdate()->first();

            if ($expectedVersion !== null && ! hash_equals($this->version($page), $expectedVersion)) {
                throw new StaleBuilderException;
            }

            $roots = $page->blocks()->whereNull('parent_id')->orderBy('order')->get()->keyBy('id');

            $this->assertIdsAreRootBlocksOfPage($items, $roots);
            $plan = $this->plan($items, $roots);

            $this->apply($page, $plan, $roots);

            return $page->blocks()->whereNull('parent_id')->orderBy('order')->get(['id', 'type', 'order'])->toArray();
        });
    }

    /** Refuse (sans rien ecrire) tout id hors des blocs racine de la page, ou en double. */
    private function assertIdsAreRootBlocksOfPage(array $items, Collection $roots): void
    {
        $seen = [];

        foreach ($items as $i => $item) {
            $id = $item['id'] ?? null;

            if ($id === null) {
                continue;
            }

            if (! $roots->has($id)) {
                throw ValidationException::withMessages(["blocks.$i.id" => 'Ce bloc n\'est pas un bloc racine de cette page.']);
            }

            if (isset($seen[$id])) {
                throw ValidationException::withMessages(["blocks.$i.id" => 'Ce bloc apparait plusieurs fois.']);
            }

            $seen[$id] = true;
        }
    }

    /**
     * Valide tous les blocs avant la moindre ecriture.
     *
     * @return list<array{block: ?PageBlock, type: string, data: ?array}> data null = bloc intact
     */
    private function plan(array $items, Collection $roots): array
    {
        $plan = [];

        foreach ($items as $i => $item) {
            $block = isset($item['id']) ? $roots->get($item['id']) : null;
            $type = $block->type ?? $item['type'];

            // Bloc que le builder ne connait pas : on ignore toute donnee envoyee.
            if ($block && ! $this->isEditable($type)) {
                $plan[] = ['block' => $block, 'type' => $type, 'data' => null];

                continue;
            }

            if ($block && $item['type'] !== $type) {
                throw ValidationException::withMessages(["blocks.$i.type" => 'Le type d\'un bloc existant ne peut pas changer.']);
            }

            if (! $this->isEditable($type)) {
                throw ValidationException::withMessages(["blocks.$i.type" => 'Ce type de bloc n\'est pas modifiable dans le Page Builder.']);
            }

            $plan[] = [
                'block' => $block,
                'type' => $type,
                'data' => $this->validatedData($i, $type, $item['data'] ?? [], $block),
                'media_id' => $this->validatedMediaId($i, $type, $item['media_id'] ?? null, $block),
            ];
        }

        return $plan;
    }

    private function validatedData(int $index, string $type, array $input, ?PageBlock $block): array
    {
        $rules = BlockRegistry::rulesFor($type);
        $mediaFields = $rules instanceof DeclaresMediaFields ? $rules->mediaFields() : [];
        $validator = Validator::make($input, Arr::except($rules->rules($block === null, $block), $mediaFields), $rules->messages());

        if ($validator->fails()) {
            $errors = [];
            foreach ($validator->errors()->messages() as $field => $messages) {
                $errors["blocks.$index.data.$field"] = $messages;
            }

            throw ValidationException::withMessages($errors);
        }

        $data = array_merge($validator->validated(), $rules->casts(Request::create('/', 'POST', $input)));

        return $this->blockMedia->stripMediaFields($data, $type);
    }

    /**
     * media_id : image choisie dans la mediatheque (types a image unique).
     * Obligatoire a la creation ; absent ensuite = l'image actuelle est conservee.
     */
    private function validatedMediaId(int $index, string $type, mixed $mediaId, ?PageBlock $block): ?int
    {
        if (! in_array($type, self::mediaTypes(), true)) {
            return null;
        }

        if ($mediaId === null || $mediaId === '') {
            if ($block === null) {
                throw ValidationException::withMessages(["blocks.$index.media_id" => 'Choisissez une image dans la mediatheque.']);
            }

            return null;
        }

        $isImage = Media::query()->whereKey((int) $mediaId)->where('mime_type', 'like', 'image/%')->exists();

        if (! $isImage) {
            throw ValidationException::withMessages(["blocks.$index.media_id" => 'Image introuvable : choisissez une image de la mediatheque.']);
        }

        return (int) $mediaId;
    }

    private function apply(Page $page, array $plan, Collection $roots): void
    {
        $order = 0;
        $keptIds = [];

        foreach ($plan as $entry) {
            $attributes = ['order' => $order++];

            if ($entry['data'] !== null) {
                $attributes['data'] = $entry['data'];
            }

            if ($entry['block']) {
                $entry['block']->update($attributes);
                $this->attachChosenMedia($entry['block'], $entry['media_id'] ?? null);
                $keptIds[] = $entry['block']->id;
            } else {
                $created = $page->blocks()->create($attributes + ['type' => $entry['type']]);
                $this->attachChosenMedia($created, $entry['media_id'] ?? null);
            }
        }

        // Blocs racine absents du payload : un type editable est supprime, les
        // autres sont conserves et places a la suite, dans leur ordre actuel.
        foreach ($roots as $block) {
            if (in_array($block->id, $keptIds, true)) {
                continue;
            }

            if ($this->isEditable($block->type)) {
                // Blocs feuilles : pas d'enfants, donc pas besoin de la recursion du controleur.
                $block->detachAndPruneOrphanMedia($block);
                $block->delete();
            } else {
                $block->update(['order' => $order++]);
            }
        }
    }

    private function attachChosenMedia(PageBlock $block, ?int $mediaId): void
    {
        if ($mediaId !== null) {
            $this->blockMedia->attachExisting($block, $mediaId);
        }
    }

    /** Empreinte de l'etat des blocs racine editables (detecte une sauvegarde depuis un builder perime). */
    public function version(Page $page): string
    {
        $state = $page->blocks()
            ->whereNull('parent_id')
            ->whereIn('type', self::editableTypes())
            ->with('media')
            ->orderBy('order')
            ->orderBy('id')
            ->get()
            ->map(fn (PageBlock $block) => [
                $block->id,
                $block->type,
                $block->order,
                $this->canonical($block->data ?? []),
                $block->media->pluck('id')->all(),
            ])
            ->all();

        return sha1(json_encode($state, JSON_THROW_ON_ERROR));
    }

    /** Trie les cles des tableaux associatifs : l'ordre des cles JSON ne doit pas changer l'empreinte. */
    private function canonical(mixed $value): mixed
    {
        if (! is_array($value)) {
            return $value;
        }

        $value = array_map(fn ($item) => $this->canonical($item), $value);

        if (! array_is_list($value)) {
            ksort($value);
        }

        return $value;
    }

    /** Types editables : cles du catalogue (source unique). */
    public static function editableTypes(): array
    {
        return array_keys(BlockFieldCatalog::all());
    }

    /** Types a image unique : entrees du catalogue marquees media. */
    public static function mediaTypes(): array
    {
        return array_keys(array_filter(BlockFieldCatalog::all(), fn (array $definition) => $definition['media'] ?? false));
    }

    private function isEditable(string $type): bool
    {
        return in_array($type, self::editableTypes(), true);
    }
}
