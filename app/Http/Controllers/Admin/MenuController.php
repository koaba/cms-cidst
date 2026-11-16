<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\MenuRequest;
use App\Models\Menu;
use App\Traits\HasOrphanMediaCleanup;

class MenuController extends Controller
{
    use HasOrphanMediaCleanup;

    public function index()
    {
        $grouped = Menu::whereNull('parent_id')
            ->with('children.children')
            ->orderBy('order')
            ->orderBy('id')
            ->get()
            ->groupBy('location');

        $sections = collect(Menu::LOCATIONS)->map(fn (string $label, string $location) => [
            'label' => $label,
            'menus' => $grouped->get($location, collect()),
        ]);

        return view('admin.menus.index', compact('sections'));
    }

    public function create()
    {
        $parents = $this->availableParents();

        return view('admin.menus.create', compact('parents'));
    }

    public function store(MenuRequest $request)
    {
        Menu::create($request->menuData() + ['location' => Menu::MAIN]);

        return redirect()->route('admin.menus.index')->with('success', 'Menu créé.');
    }

    public function edit(Menu $menu)
    {
        $excludedIds = array_merge([$menu->id], $menu->descendantIds());
        $parents = $this->availableParents($excludedIds);

        return view('admin.menus.edit', compact('menu', 'parents'));
    }

    public function update(MenuRequest $request, Menu $menu)
    {
        $data = $request->menuData();

        $excludedIds = array_merge([$menu->id], $menu->descendantIds());
        if (isset($data['parent_id']) && in_array($data['parent_id'], $excludedIds)) {
            return back()
                ->withErrors(['parent_id' => 'Un menu ne peut pas être son propre parent ou descendant.'])
                ->withInput();
        }

        if (($data['location'] ?? $menu->location) !== Menu::MAIN && $menu->children()->exists()) {
            return back()
                ->withErrors(['location' => 'Cet élément a des sous-menus : déplacez-les ou supprimez-les avant de le sortir du menu principal.'])
                ->withInput();
        }

        $menu->update($data);

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

    private function availableParents(array $excludedIds = [])
    {
        return Menu::where('location', Menu::MAIN)
            ->whereNotIn('id', $excludedIds)
            ->get()
            ->filter(fn ($menu) => $menu->depth() < 2)
            ->sortBy('order');
    }
}
