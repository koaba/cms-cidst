<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\NewsTicker;
use Illuminate\Http\Request;


class NewsTickerController extends Controller
{
    public function index()
    {
        $newsTickers = NewsTicker::orderBy('order')->get();
        return view('admin.news-tickers.index', compact('newsTickers'));
    }

    public function create()
    {
        return view('admin.news-tickers.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'content' => 'required|string|max:255',
            'link_url' => 'nullable|string|max:255',
            'order' => 'nullable|integer',
            'is_active' => 'boolean',
        ]);

        $validated['is_active'] = $request->boolean('is_active');
        $validated['order'] = $validated['order'] ?? 0;

        NewsTicker::create($validated);

        return redirect()->route('admin.news-tickers.index')->with('success', 'Actualité créée.');
    }

    public function edit(NewsTicker $newsTicker)
    {
        return view('admin.news-tickers.edit', compact('newsTicker'));
    }

    public function update(Request $request, NewsTicker $newsTicker)
    {
        $validated = $request->validate([
            'content' => 'required|string|max:255',
            'link_url' => 'nullable|string|max:255',
            'order' => 'nullable|integer',
            'is_active' => 'boolean',
        ]);

        $validated['is_active'] = $request->boolean('is_active');
        $validated['order'] = $validated['order'] ?? 0;

        $newsTicker->update($validated);

        return redirect()->route('admin.news-tickers.index')->with('success', 'Actualité mise à jour.');
    }

    public function destroy(NewsTicker $newsTicker)
    {
        $newsTicker->delete();

        return redirect()->route('admin.news-tickers.index')->with('success', 'Actualité supprimée.');
    }

    public function show(NewsTicker $newsTicker)
    {
        abort(404);
    }
}