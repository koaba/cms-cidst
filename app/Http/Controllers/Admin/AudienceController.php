<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\Dashboard\AudienceService;
use App\Services\Dashboard\DashboardPeriod;
use Illuminate\Http\Request;

class AudienceController extends Controller
{
    public function __invoke(Request $request, AudienceService $service)
    {
        $period = DashboardPeriod::from($request->string('period')->toString());

        // "Global" n'existe pas ici : le cumul mensuel couvre deja l'historique.
        if ($period->isGlobal()) {
            $period = DashboardPeriod::from(null);
        }

        return view('admin.audience', [
            'period' => $period,
            'summary' => $service->summary($period->start()),
            'monthly' => $service->monthly(),
        ]);
    }
}
