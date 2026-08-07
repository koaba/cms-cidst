<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SiteSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class SiteSettingController extends Controller
{
    public function edit()
    {
        $settings = SiteSetting::current();
        return view('admin.settings.edit', compact('settings'));
    }

    public function update(Request $request)
    {
        $validated = $request->validate([
            'hero_eyebrow' => 'nullable|string|max:255',
            'hero_eyebrow_size' => 'nullable|in:xs,sm,base,lg,xl',
            'hero_title' => 'required|string|max:255',
            'hero_subtitle' => 'nullable|string|max:500',
            'logo' => 'nullable|image|max:2048',
            'cta_primary_label' => 'nullable|string|max:100',
            'cta_primary_target' => 'nullable|string|max:255',
            'cta_secondary_label' => 'nullable|string|max:100',
            'cta_secondary_target' => 'nullable|string|max:255',
            'primary_color' => ['nullable', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'secondary_color' => ['nullable', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'news_ticker_direction' => 'nullable|in:horizontal,vertical',
            'pages_grid_columns' => 'nullable|in:2,3,4',
            'pages_image_size' => 'nullable|in:small,medium,large',
        ]);

        $settings = SiteSetting::current();

        if ($request->hasFile('logo')) {
            if ($settings->logo_path) {
                Storage::disk('public')->delete($settings->logo_path);
            }
            $validated['logo_path'] = $request->file('logo')->store('logos', 'public');
        }

        unset($validated['logo']);

        $settings->update($validated);

        return redirect()->route('admin.settings.edit')->with('success', 'Réglages mis à jour.');
    }
}