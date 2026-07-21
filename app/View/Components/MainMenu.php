<?php

namespace App\View\Components;

use App\Models\Menu;
use App\Models\SiteSetting;
use Illuminate\View\Component;
use Illuminate\View\View;

class MainMenu extends Component
{
    public $items;
    public $settings;

    public function __construct()
    {
        $this->items = Menu::where('is_active', true)->orderBy('order')->get();
        $this->settings = SiteSetting::current();
    }

    public function render(): View
    {
        return view('components.main-menu');
    }
}