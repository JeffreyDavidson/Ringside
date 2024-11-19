<?php

declare(strict_types=1);

namespace App\Livewire\TagTeams;

use App\Livewire\Concerns\ShowTableTrait;
use App\Models\StableTagTeam;
use Illuminate\Database\Eloquent\Builder;
use Rappasoft\LaravelLivewireTables\DataTableComponent;
use Rappasoft\LaravelLivewireTables\Views\Column;
use Rappasoft\LaravelLivewireTables\Views\Columns\DateColumn;

class PreviousStablesTable extends DataTableComponent
{
    use ShowTableTrait;

    protected string $databaseTableName = 'stables_tag_teams';

    protected string $resourceName = 'stables';

    public ?int $tagTeamId;

    public function builder(): Builder
    {
        if (! isset($this->tagTeamId)) {
            throw new \Exception("You didn't specify a tag team");
        }

        return StableTagTeam::query()
            ->where('tag_team_id', $this->tagTeamId)
            ->whereNotNull('left_at')
            ->orderByDesc('joined_at');
    }

    public function configure(): void
    {
        $this->addAdditionalSelects([
            'stables_tag_teams.stable_id as stable_id',
        ]);
    }

    public function columns(): array
    {
        return [
            Column::make(__('stables.name'), 'stable.name')
                ->searchable(),
            DateColumn::make(__('stables.date_joined'), 'joined_at')
                ->outputFormat('Y-m-d'),
            DateColumn::make(__('stables.date_left'), 'left_at')
                ->outputFormat('Y-m-d'),
        ];
    }
}
