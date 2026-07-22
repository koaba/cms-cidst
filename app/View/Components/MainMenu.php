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
        $this->items = Menu::whereNull('parent_id')
            ->where('is_active', true)
            ->with(['children' => function ($query) {
                $query->where('is_active', true)->orderBy('order');
            }])
            ->orderBy('order')
            ->get();
        $this->settings = SiteSetting::current();
    }
    public function render(): View
    {
        return view('components.main-menu');
    }
}