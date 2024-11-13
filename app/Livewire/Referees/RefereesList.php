<?php

declare(strict_types=1);

namespace App\Livewire\Referees;

use App\Builders\RefereeBuilder;
use App\Models\Referee;
use Illuminate\Contracts\View\View;
use Livewire\Component;

class RefereesList extends Component
{
    /**
     * Determines if the filters should be shown.
     */
    public bool $showFilters = false;

    /**
     * Shows list of accepted filters and direction to be displayed.
     *
     * @var array<string, string>
     */
    public array $filters = [
        'search' => '',
    ];

    /**
     * @var array<int>
     */
    public array $selectedRefereeIds = [];

    /**
     * @var array<int>
     */
    public array $refereeIdsOnPage = [];

    /**
     * Display a listing of the resource.
     */
    public function render(): View
    {
        $query = Referee::query()
            ->when(
                $this->filters['search'],
                function (RefereeBuilder $query, string $search) {
                    $query->where('first_name', 'like', '%'.$search.'%')
                        ->orWhere('last_name', 'like', '%'.$search.'%');
                }
            )
            ->oldest('last_name');

        $referees = $query->paginate();

        $this->refereeIdsOnPage = $referees->map(fn (Referee $referee) => (string) $referee->id)->toArray();

        return view('livewire.referees.referees-list', [
            'referees' => $referees,
        ]);
    }
}
