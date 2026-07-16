<?php

namespace App\View\Components;

use App\Models\NewsTicker as NewsTickerModel;
use Illuminate\View\Component;

class NewsTicker extends Component
{
    public $items;

    public function __construct()
    {
        $this->items = NewsTickerModel::where('is_active', true)->orderBy('order')->get();
    }

    public function render()
    {
        return view('components.news-ticker');
    }
}