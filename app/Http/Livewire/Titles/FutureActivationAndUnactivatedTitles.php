<?php

namespace App\Http\Livewire\Titles;

use App\Models\Title;
use Livewire\Component;
use Livewire\WithPagination;

class FutureActivationAndUnactivatedTitles extends Component
{
    use WithPagination;

    /**
     * Number of items to display on each page.
     *
     * @var int
     */
    public $perPage = 10;

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\View\View
     */
    public function render()
    {
        $futureActivationAndUnactivatedTitles = Title::query()
            ->withFutureActivation()
            ->orWhere
            ->unactivated()
            ->withFirstActivatedAtDate()
            ->orderByNullsLast('first_activated_at')
            ->orderBy('name')
            ->paginate();

        return view('livewire.titles.future-activation-and-unactivated-titles', [
            'futureActivationAndUnactivatedTitles' => $futureActivationAndUnactivatedTitles,
        ]);
    }
}
