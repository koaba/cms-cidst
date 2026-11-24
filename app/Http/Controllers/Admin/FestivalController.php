<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateFestivalSettingsRequest;
use App\Services\Festival\FestivalCalendar;
use App\Services\Festival\FestivalSettings;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;

class FestivalController extends Controller
{
    public function edit(FestivalSettings $settings, FestivalCalendar $calendar): View
    {
        $today = now();

        $festivals = collect((array) config('festivals.festivals', []))
            ->map(fn (array $festival, string $key): array => [
                'label' => $festival['label'],
                'window' => $calendar->nextWindow($key, $today),
                'options' => $settings->options($key),
                'preview' => $settings->strip($key),
            ]);

        return view('admin.festivals.edit', [
            'festivals' => $festivals,
            'enabled' => $settings->switchedOn(),
            'envEnabled' => (bool) config('festivals.enabled', true),
            'simulatedOn' => $settings->simulatedOn(),
            'icons' => (array) config('festivals.icons', []),
            'placements' => FestivalSettings::PLACEMENTS,
            'slots' => FestivalSettings::STRIP_SLOTS,
        ]);
    }

    public function update(UpdateFestivalSettingsRequest $request, FestivalSettings $settings): RedirectResponse
    {
        $settings->update($request->validated());

        return redirect()->route('admin.festivals.edit')->with('success', "R\u{e9}glages des f\u{ea}tes mis \u{e0} jour.");
    }
}
