<?php

namespace App\Http\Livewire\Titles;

use App\Models\Title;
use Livewire\Component;
use Livewire\WithPagination;

class ActiveTitles extends Component
{
    use WithPagination;

    public $perPage = 10;

    public function paginationView()
    {
        return 'pagination.datatables';
    }

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
