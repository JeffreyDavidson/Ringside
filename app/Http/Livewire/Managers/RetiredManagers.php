<?php

namespace App\Http\Livewire\Managers;

use App\Http\Livewire\BaseComponent;
use App\Models\Manager;

class RetiredManagers extends BaseComponent
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\View\View
     */
    public function render()
    {
        $retiredManagers = Manager::query()
            ->retired()
            ->withCurrentRetiredAtDate()
            ->orderByCurrentRetiredAtDate()
            ->orderBy('last_name')
            ->paginate($this->perPage);

        return view('livewire.managers.retired-managers', [
            'retiredManagers' => $retiredManagers,
        ]);
    }
}
