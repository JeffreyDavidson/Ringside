<?php

declare(strict_types=1);

namespace App\Livewire\Wrestlers;

use App\Models\Wrestler;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\DB;
use Rappasoft\LaravelLivewireTables\DataTableComponent;
use Rappasoft\LaravelLivewireTables\Views\Column;

class PreviousTitleChampionshipsList extends DataTableComponent
{
    /**
     * Wrestler to use for component.
     */
    public Wrestler $wrestler;

    /**
     * Undocumented function.
     */
    public function mount(Wrestler $wrestler): void
    {
        $this->wrestler = $wrestler;
    }

    public function configure(): void
    {
    }

    public function columns(): array
    {
        return [
            Column::make(__('titles.name'), 'name'),
            Column::make(__('championships.previous_champion'), 'previous_champion'),
            Column::make(__('championships.dates_held'), 'dates_held'),
            Column::make(__('championships.days_held'), 'days_held'),
        ];
    }

    /**
     * Display a listing of the resource.
     */
    public function render(): View
    {
        $query = $this->wrestler
            ->previousTitleChampionships()
            ->with('title')
            ->addSelect(
                'title_championships.title_id',
                'title_championships.won_at',
                'title_championships.lost_at',
                DB::raw('DATEDIFF(COALESCE(lost_at, NOW()), won_at) AS days_held_count')
            );

        $previousTitleChampionships = $query->paginate();

        return view('livewire.wrestlers.previous-title-championships.previous-title-championships-list', [
            'previousTitleChampionships' => $previousTitleChampionships,
        ]);
    }
}
