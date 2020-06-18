<?php

namespace App\Http\Livewire\Stables;

use App\Models\Stable;
use Livewire\Component;
use Livewire\WithPagination;

class RetiredStables extends Component
{
    use WithPagination;

    public $perPage = 10;

    public function render()
    {
        $retiredStables = Stable::query()
            ->retired()
            ->withCurrentRetiredAtDate()
            ->orderByCurrentRetiredAtDate()
            ->orderBy('name')
            ->paginate($this->perPage);

        return view('livewire.stables.retired-stables', [
            'retiredStables' => $retiredStables
        ]);
    }
}
