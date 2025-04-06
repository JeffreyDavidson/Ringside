<?php

declare(strict_types=1);

namespace App\Livewire\TagTeams\Tables;

use App\Livewire\Concerns\ShowTableTrait;
use App\Models\TagTeam;
use App\Models\TitleChampionship;
use Illuminate\Database\Eloquent\Builder;
use Rappasoft\LaravelLivewireTables\DataTableComponent;
use Rappasoft\LaravelLivewireTables\Views\Column;

class PreviousTitleChampionshipsTable extends DataTableComponent
{
    use ShowTableTrait;

    protected string $databaseTableName = 'tittle_championships';

    protected string $resourceName = 'title championships';

    /**
     * Tag Team to use for component.
     */
    public ?int $tagTeamId;

    /**
     * @return Builder<TitleChampionship>
     */
    public function builder(): Builder
    {
        if (! isset($this->tagTeamId)) {
            throw new \Exception("You didn't specify a tag team");
        }

        return TitleChampionship::query()
            ->whereHasMorph(
                'newChampion',
                [TagTeam::class],
                function (Builder $query) {
                    $query->whereIn('id', [$this->tagTeamId]);
                }
            );
    }

    public function configure(): void {}

    /**
     * Undocumented function
     *
     * @return array<int, Column>
     */
    public function columns(): array
    {
        return [
            Column::make(__('titles.name'), 'name'),
            Column::make(__('championships.previous_champion'), 'previous_champion'),
            Column::make(__('championships.dates_held'), 'dates_held'),
            Column::make(__('championships.reign_length'), 'reign_length'),
        ];
    }
}
