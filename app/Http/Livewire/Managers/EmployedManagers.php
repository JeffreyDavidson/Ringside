<?php

namespace App\Http\Livewire\Managers;

use App\Http\Livewire\BaseComponent;
use App\Models\Manager;

class EmployedManagers extends BaseComponent
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\View\View
     */
    public function render()
    {
        $employedManagers = Manager::query()
            ->employed()
            ->withFirstEmployedAtDate()
            ->orderByFirstEmployedAtDate()
            ->orderBy('last_name')
            ->paginate($this->perPage);

        return view('livewire.managers.employed-managers', [
            'employedManagers' => $employedManagers,
        ]);
    }
}
