<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class GuideController extends Controller
{
    public function __invoke(Request $request)
    {
        $menus = collect(config('admin_menu'))
            ->filter(fn ($item) => ! isset($item['roles']) || $request->user()->hasAnyRole($item['roles']));

        return view('admin.guide', compact('menus'));
    }
}
