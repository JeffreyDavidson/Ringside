<?php

namespace App\Http\Livewire\Titles;

use App\Http\Livewire\BaseComponent;
use App\Models\Title;

class RetiredTitles extends BaseComponent
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\View\View
     */
    public function render()
    {
        $retiredTitles = Title::query()
            ->retired()
            ->withCurrentRetiredAtDate()
            ->orderByCurrentRetiredAtDate()
            ->orderBy('name')
            ->paginate($this->perPage);

        return view('livewire.titles.retired-titles', [
            'retiredTitles' => $retiredTitles,
        ]);
    }
}
