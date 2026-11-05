<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateSiteSettingRequest;
use App\Models\SiteSetting;
use App\Services\SiteSettingService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;

class SiteSettingController extends Controller
{
    public function edit(): View
    {
        $settings = SiteSetting::current();

        return view('admin.settings.edit', compact('settings'));
    }

    public function update(UpdateSiteSettingRequest $request, SiteSettingService $service): RedirectResponse
    {
        $service->update(SiteSetting::current(), $request->validated());

        return redirect()->route('admin.settings.edit')->with('success', 'Réglages mis à jour.');
    }
}
