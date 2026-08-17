<?php
namespace App\Http\Controllers\Admin;
use App\Http\Controllers\Controller;
use App\Models\Menu;
use App\Traits\HasOrphanMediaCleanup;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class MenuController extends Controller
{
    use HasOrphanMediaCleanup;

    private const MAX_PDFS = 10;

    public function index()
    {
        $menus = Menu::whereNull('parent_id')
            ->with('children.children')
            ->orderBy('order')
            ->get();
        return view('admin.menus.index', compact('menus'));
    }

    public function create()
    {
        $parents = $this->availableParents();
        return view('admin.menus.create', compact('parents'));
    }

    public function store(Request $request)
    {
        $validated = $this->validateMenu($request);

        $menu = Menu::create($this->extractMenuData($validated, $request));

        $this->syncPdfs($request, $menu);

        return redirect()->route('admin.menus.index')->with('success', 'Menu créé.');
    }

    public function edit(Menu $menu)
    {
        $menu->load('media');
        $excludedIds = array_merge([$menu->id], $menu->descendantIds());
        $parents = $this->availableParents($excludedIds);
        return view('admin.menus.edit', compact('menu', 'parents'));
    }

    public function update(Request $request, Menu $menu)
    {
        $validated = $this->validateMenu($request, $menu);

        $excludedIds = array_merge([$menu->id], $menu->descendantIds());
        if (isset($validated['parent_id']) && in_array($validated['parent_id'], $excludedIds)) {
            return back()
                ->withErrors(['parent_id' => 'Un menu ne peut pas être son propre parent ou descendant.'])
                ->withInput();
        }

        $menu->update($this->extractMenuData($validated, $request));

        $this->syncPdfs($request, $menu, isUpdate: true);

        return redirect()->route('admin.menus.index')->with('success', 'Menu mis à jour.');
    }

    public function destroy(Menu $menu)
    {
        $childrenCount = $menu->children()->count();

        if ($childrenCount > 0) {
            $childrenLabels = $menu->children()->pluck('label')->join(', ');
            return back()->with('error', "Impossible de supprimer ce menu : il contient {$childrenCount} sous-menu(s) ({$childrenLabels}). Déplacez-les ou supprimez-les d'abord.");
        }

        $this->detachAndPruneOrphanMedia($menu);

        $menu->delete();

        return redirect()->route('admin.menus.index')->with('success', 'Menu supprimé.');
    }

    /* ------------------------------------------------------------------ */

    private function validateMenu(Request $request, ?Menu $menu = null): array
    {
        $validator = \Illuminate\Support\Facades\Validator::make($request->all(), [
            'label' => 'required|string|max:255',
            'target' => 'required|string|max:255',
            'order' => 'nullable|integer',
            'is_active' => 'nullable|boolean',
            'parent_id' => 'nullable|exists:menus,id',
            'pdfs' => 'nullable|array|max:' . self::MAX_PDFS,
            'pdfs.*' => 'file|mimes:pdf|max:10240',
            'existing_media' => 'nullable|array',
            'existing_media.*' => 'integer|exists:media,id',
            'delete_pdfs' => 'nullable|array',
            'delete_pdfs.*' => [
                'integer',
                $menu
                    ? Rule::exists('mediables', 'media_id')
                        ->where('mediable_type', Menu::class)
                        ->where('mediable_id', $menu->id)
                    : 'exists:media,id',
            ],
        ]);

        $validator->after(function ($validator) use ($request, $menu) {
            $currentCount = $menu ? $menu->media()->count() : 0;
            $toDelete = count($request->input('delete_pdfs', []));
            $incoming = count($request->file('pdfs', [])) + count($request->input('existing_media', []));
            if (($currentCount - $toDelete + $incoming) > self::MAX_PDFS) {
                $validator->errors()->add('pdfs', 'Un menu ne peut pas avoir plus de ' . self::MAX_PDFS . ' documents PDF.');
            }
        });

        return $validator->validate();
    }

    /**
     * Isole les champs propres au modele Menu (exclut les champs media
     * pdfs/existing_media/delete_pdfs geres separement par syncPdfs()).
     */
    private function extractMenuData(array $validated, Request $request): array
    {
        return [
            'label' => $validated['label'],
            'target' => $validated['target'],
            'order' => $validated['order'] ?? 0,
            'is_active' => $request->boolean('is_active'),
            'parent_id' => $validated['parent_id'] ?? null,
        ];
    }

    private function syncPdfs(Request $request, Menu $menu, bool $isUpdate = false): void
    {
        if ($isUpdate && $request->filled('delete_pdfs')) {
            $menu->detachOwnedMedia($request->input('delete_pdfs'));
        }

        if ($request->filled('existing_media')) {
            $menu->attachExistingMedia($request->input('existing_media'));
        }

        if ($request->hasFile('pdfs')) {
            $menu->attachUploadedFiles($request->file('pdfs'), 'menus/pdfs');
        }
    }

    private function availableParents(array $excludedIds = [])
    {
        return Menu::whereNotIn('id', $excludedIds)
            ->get()
            ->filter(fn ($menu) => $menu->depth() < 2)
            ->sortBy('order');
    }
}