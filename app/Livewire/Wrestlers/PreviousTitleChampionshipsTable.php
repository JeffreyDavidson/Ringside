<?php

declare(strict_types=1);

namespace App\Livewire\Wrestlers;

use App\Livewire\Concerns\ShowTableTrait;
use App\Models\TitleChampionship;
use App\Models\Wrestler;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Rappasoft\LaravelLivewireTables\DataTableComponent;
use Rappasoft\LaravelLivewireTables\Views\Column;

class PreviousTitleChampionshipsTable extends DataTableComponent
{
    use ShowTableTrait;

    protected string $databaseTableName = 'title_championships';

    protected string $resourceName = 'title championships';

    public ?Wrestler $wrestler;

    public function builder(): Builder
    {
        if (! isset($this->wrestler)) {
            throw new \Exception("You didn't specify a wrestler");
        }

        return TitleChampionship::query()
            ->whereHas('currentChampion', function ($query) {
                $query->whereMorphedTo('champion', $this->wrestler);
            })
            ->whereNotNull('left_at')
            ->orderByDesc('joined_at');
    }

    public function configure(): void
    {
        $this->addAdditionalSelects([
            'title_championships.title_id',
            'title_championships.won_at',
            'title_championships.lost_at',
            DB::raw('DATEDIFF(COALESCE(lost_at, NOW()), won_at) AS days_held_count'),
        ]);
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
}
