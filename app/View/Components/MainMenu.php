<?php

namespace App\View\Components;

use Illuminate\View\Component;
use Illuminate\View\View;

class MainMenu extends Component
{
    public $items;

    public function __construct()
    {
        $this->items = \App\Models\Menu::where('is_active', true)->orderBy('order')->get();
    }

    public function render(): View
    {
        return view('components.main-menu');
    }
}