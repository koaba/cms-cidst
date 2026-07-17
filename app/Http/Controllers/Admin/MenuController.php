<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Menu;
use Illuminate\Http\Request;

class MenuController extends Controller
{
    public function index()
    {
        $menus = Menu::orderBy('order')->get();
        return view('admin.menus.index', compact('menus'));
    }

    public function create()
    {
        return view('admin.menus.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'label' => 'required|string|max:255',
            'target' => 'required|string|max:255',
            'order' => 'nullable|integer',
            'is_active' => 'nullable|boolean',
        ]);

        Menu::create($validated);

        return redirect()->route('admin.menus.index')->with('success', 'Menu créé.');
    }

    public function edit(Menu $menu)
    {
        return view('admin.menus.edit', compact('menu'));
    }

    public function update(Request $request, Menu $menu)
    {
        $validated = $request->validate([
            'label' => 'required|string|max:255',
            'target' => 'required|string|max:255',
            'order' => 'nullable|integer',
            'is_active' => 'nullable|boolean',
        ]);

        $menu->update($validated);

        return redirect()->route('admin.menus.index')->with('success', 'Menu mis à jour.');
    }

    public function destroy(Menu $menu)
    {
        $menu->delete();
        return redirect()->route('admin.menus.index')->with('success', 'Menu supprimé.');
    }
}