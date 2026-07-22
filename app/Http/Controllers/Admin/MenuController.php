<?php
namespace App\Http\Controllers\Admin;
use App\Http\Controllers\Controller;
use App\Models\Menu;
use Illuminate\Http\Request;
class MenuController extends Controller
{
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
        $validated = $request->validate([
            'label' => 'required|string|max:255',
            'target' => 'required|string|max:255',
            'order' => 'nullable|integer',
            'is_active' => 'nullable|boolean',
            'parent_id' => 'nullable|exists:menus,id',
        ]);
        Menu::create($validated);
        return redirect()->route('admin.menus.index')->with('success', 'Menu cree.');
    }
    public function edit(Menu $menu)
    {
        $excludedIds = array_merge([$menu->id], $menu->descendantIds());
        $parents = $this->availableParents($excludedIds);
        return view('admin.menus.edit', compact('menu', 'parents'));
    }
    public function update(Request $request, Menu $menu)
    {
        $validated = $request->validate([
            'label' => 'required|string|max:255',
            'target' => 'required|string|max:255',
            'order' => 'nullable|integer',
            'is_active' => 'nullable|boolean',
            'parent_id' => 'nullable|exists:menus,id',
        ]);
        $menu->update($validated);
        return redirect()->route('admin.menus.index')->with('success', 'Menu mis a jour.');
    }
    public function destroy(Menu $menu)
    {
        $menu->delete();
        return redirect()->route('admin.menus.index')->with('success', 'Menu supprime.');
    }

    /**
     * Retourne la liste des menus pouvant servir de parent :
     * exclut les menus deja au niveau 2 (max 2 niveaux de sous-menus),
     * et exclut les IDs donnes (le menu courant + ses descendants en edition).
     */
    private function availableParents(array $excludedIds = [])
    {
        return Menu::whereNotIn('id', $excludedIds)
            ->get()
            ->filter(fn ($menu) => $menu->depth() < 2)
            ->sortBy('order');
    }
}