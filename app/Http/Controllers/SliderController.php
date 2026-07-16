<?php

namespace App\Http\Controllers;

use App\Models\Slider;

class SliderController extends Controller
{
    public function index()
    {
        $sliders = Slider::where('is_active', true)->orderBy('order')->get();
        return view('public.sliders.index', compact('sliders'));
    }
}