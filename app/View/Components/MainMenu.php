<?php

namespace App\View\Components;

use App\Models\SiteSetting;
use App\Support\PublicLayout;
use Illuminate\View\Component;
use Illuminate\View\View;

class MainMenu extends Component
{
    public $items;

    public $settings;

    public function __construct()
    {
        $this->items = app(PublicLayout::class)->menu();
        $this->settings = SiteSetting::current();
    }

    public function render(): View
    {
        return view('components.main-menu');
    }
}
