<?php

namespace App\Http\Livewire\Titles;

use App\Http\Livewire\BaseComponent;
use App\Models\Title;

class InactiveTitles extends BaseComponent
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\View\View
     */
    public function render()
    {
        $inactiveTitles = Title::query()
            ->inactive()
            ->withLastDeactivationDate()
            ->orderByLastDeactivationDate()
            ->orderBy('name')
            ->paginate($this->perPage);

        return view('livewire.titles.inactive-titles', [
            'inactiveTitles' => $inactiveTitles,
        ]);
    }
}
