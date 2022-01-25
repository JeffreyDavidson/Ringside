<?php

namespace App\Http\Livewire\Titles;

use App\Http\Livewire\BaseComponent;
use App\Models\Title;

class ActiveTitles extends BaseComponent
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\View\View
     */
    public function render()
    {
        $activeTitles = Title::query()
            ->active()
            ->withFirstActivatedAtDate()
            ->orderByFirstActivatedAtDate()
            ->orderBy('name')
            ->paginate($this->perPage);

        return view('livewire.titles.active-titles', [
            'activeTitles' => $activeTitles,
        ]);
    }
}
