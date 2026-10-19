<?php

namespace App\Http\Controllers;

use App\Models\Slider;

class HomeController extends Controller
{
    public function __invoke()
    {
        $homeSliders = Slider::with('media')->where('is_active', true)->orderBy('order')->take(5)->get();

        return view('welcome', compact('homeSliders'));
    }
}
