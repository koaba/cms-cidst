<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\Dashboard\AudienceService;
use Illuminate\Http\Request;

class AudienceController extends Controller
{
    private const PERIODS = ['week' => 7, 'month' => 30, 'year' => 365];

    public function __invoke(Request $request, AudienceService $service)
    {
        $period = $request->query('period');
        $period = is_string($period) && isset(self::PERIODS[$period]) ? $period : 'month';

        return view('admin.audience', [
            'period' => $period,
            'summary' => $service->summary(now()->subDays(self::PERIODS[$period])),
        ]);
    }
}
