<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SiteSetting;
use Illuminate\Http\Request;

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
            'hero_title' => 'required|string|max:255',
            'hero_subtitle' => 'nullable|string|max:500',
            'cta_primary_label' => 'nullable|string|max:100',
            'cta_primary_target' => 'nullable|string|max:255',
            'cta_secondary_label' => 'nullable|string|max:100',
            'cta_secondary_target' => 'nullable|string|max:255',
        ]);

        SiteSetting::current()->update($validated);

        return redirect()->route('admin.settings.edit')->with('success', 'Réglages mis à jour.');
    }
}