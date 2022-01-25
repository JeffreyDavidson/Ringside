<?php

namespace App\Http\Livewire\Stables;

use App\Http\Livewire\BaseComponent;
use App\Models\Stable;

class RetiredStables extends BaseComponent
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\View\View
     */
    public function render()
    {
        $retiredStables = Stable::query()
            ->retired()
            ->withCurrentRetiredAtDate()
            ->orderByCurrentRetiredAtDate()
            ->orderBy('name')
            ->paginate($this->perPage);

        return view('livewire.stables.retired-stables', [
            'retiredStables' => $retiredStables,
        ]);
    }
}
