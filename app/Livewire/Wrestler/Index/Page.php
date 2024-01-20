<?php

declare(strict_types=1);

namespace App\Livewire\Wrestler\Index;

use Illuminate\Contracts\View\View;
use Livewire\Component;

class Page extends Component
{
    public Filters $filters;

    public function render(): View
    {
        return view('livewire.wrestler.index.page');
    }
}
