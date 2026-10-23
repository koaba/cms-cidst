<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Article;
use App\Services\Dashboard\DashboardStatsService;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index(Request $request, DashboardStatsService $statsService)
    {
        $isSuperAdmin = $request->user()->hasRole('Super Admin');

        $data = [
            'recentArticles' => Article::query()
                ->when($isSuperAdmin, fn ($query) => $query->with('user'))
                ->latest('updated_at')
                ->take(5)
                ->get(),
        ];

        if ($isSuperAdmin) {
            $period = $request->query('period');
            $data += $statsService->forPeriod(is_string($period) ? $period : null);
        }

        return view('admin.dashboard', $data);
    }
}
