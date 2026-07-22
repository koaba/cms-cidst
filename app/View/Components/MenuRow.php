<?php
namespace App\View\Components;
use App\Models\Menu;
use Illuminate\View\Component;
use Illuminate\View\View;
class MenuRow extends Component
{
    public Menu $menu;
    public int $depth;

    public function __construct(Menu $menu, int $depth = 0)
    {
        $this->menu = $menu;
        $this->depth = $depth;
    }
    public function render(): View
    {
        return view('components.menu-row');
    }
}