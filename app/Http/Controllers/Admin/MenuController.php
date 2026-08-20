<?php
namespace App\Http\Controllers\Admin;
use App\Http\Controllers\Controller;
use App\Models\Menu;
use App\Traits\HasOrphanMediaCleanup;
use Illuminate\Http\Request;

class MenuController extends Controller
{
    use HasOrphanMediaCleanup;

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

        Menu::create($this->extractMenuData($validated, $request));

        return redirect()->route('admin.menus.index')->with('success', 'Menu créé.');
    }

    public function edit(Menu $menu)
    {
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
        return $request->validate([
            'label' => 'required|string|max:255',
            'target' => 'required|string|max:255',
            'order' => 'nullable|integer',
            'is_active' => 'nullable|boolean',
            'parent_id' => 'nullable|exists:menus,id',
        ]);
    }

    /**
     * Isole les champs propres au modele Menu.
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

    private function availableParents(array $excludedIds = [])
    {
        return Menu::whereNotIn('id', $excludedIds)
            ->get()
            ->filter(fn ($menu) => $menu->depth() < 2)
            ->sortBy('order');
    }
}