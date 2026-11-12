<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateSiteSettingRequest;
use App\Models\SiteSetting;
use App\Services\SiteSettingService;
use App\Services\Theme\ContrastGuard;
use App\Services\Theme\ThemePalette;
use App\Services\Theme\ThemeStyleBuilder;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;

class SiteSettingController extends Controller
{
    public function edit(ThemeStyleBuilder $builder, ContrastGuard $guard): View
    {
        $settings = SiteSetting::current();
        $palette = $this->palette($settings, $builder);
        $report = $guard->report($palette);

        return view('admin.settings.edit', compact('settings', 'palette', 'report'));
    }

    public function update(UpdateSiteSettingRequest $request, SiteSettingService $service, ThemeStyleBuilder $builder, ContrastGuard $guard): RedirectResponse
    {
        $settings = $service->update(SiteSetting::current(), $request->validated());

        $redirect = redirect()->route('admin.settings.edit')->with('success', 'Réglages mis à jour.');

        // Les écarts qui ne bloquent pas sont signalés une fois, après l'enregistrement des couleurs.
        if (is_array($request->validated('theme_colors'))) {
            $warnings = $guard->warnings($this->palette($settings, $builder));

            if ($warnings !== []) {
                $redirect->with('warnings', $warnings);
            }
        }

        return $redirect;
    }

    public function resetColors(SiteSettingService $service): RedirectResponse
    {
        $service->resetColors(SiteSetting::current());

        return redirect()->route('admin.settings.edit')->with('success', 'Couleurs réinitialisées aux valeurs par défaut.');
    }

    /** Valeur de chacun des sept rôles telle que le site les affiche. */
    private function palette(SiteSetting $settings, ThemeStyleBuilder $builder): ThemePalette
    {
        $colors = $settings->theme_colors;

        return $builder->roles(
            $settings->getRawOriginal('primary_color'),
            $settings->getRawOriginal('secondary_color'),
            $settings->getRawOriginal('theme'),
            is_array($colors) ? $colors : null,
        );
    }
}
