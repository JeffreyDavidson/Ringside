<?php

namespace App\Http\Livewire\Managers;

use App\Http\Livewire\BaseComponent;
use App\Models\Manager;

class ReleasedManagers extends BaseComponent
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\View\View
     */
    public function render()
    {
        $releasedManagers = Manager::query()
            ->released()
            ->withReleasedAtDate()
            ->orderBy('last_name')
            ->paginate($this->perPage);

        return view('livewire.managers.released-managers', [
            'releasedManagers' => $releasedManagers,
        ]);
    }
}
