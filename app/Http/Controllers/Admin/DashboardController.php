<?php

namespace App\Http\Controllers\Admin;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Models\Article;
use App\Models\Backup;
use App\Services\Dashboard\DashboardStatsService;
use App\Services\Dashboard\DashboardTodoService;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index(Request $request, DashboardStatsService $statsService, DashboardTodoService $todoService)
    {
        $isSuperAdmin = $request->user()->hasRole(UserRole::SuperAdmin);

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

            $lastBackup = Backup::done()->latest('id')->first();
            $data['backupDays'] = $lastBackup ? (int) ($lastBackup->finished_at ?? $lastBackup->created_at)->diffInDays(now(), true) : null;
            $data['backupBusy'] = Backup::active()->exists();
        }

        $data['todo'] = $todoService->counts($isSuperAdmin);

        return view('admin.dashboard', $data);
    }
}
