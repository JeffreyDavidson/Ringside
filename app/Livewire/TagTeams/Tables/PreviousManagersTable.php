<?php

declare(strict_types=1);

namespace App\Livewire\TagTeams\Tables;

use App\Livewire\Concerns\ShowTableTrait;
use App\Models\TagTeamManager;
use Illuminate\Database\Eloquent\Builder;
use Rappasoft\LaravelLivewireTables\DataTableComponent;
use Rappasoft\LaravelLivewireTables\Views\Column;
use Rappasoft\LaravelLivewireTables\Views\Columns\DateColumn;

class PreviousManagersTable extends DataTableComponent
{
    use ShowTableTrait;

    protected string $databaseTableName = 'tag_teams_managers';

    protected string $resourceName = 'managers';

    public ?int $tagTeamId;

    /**
     * @return Builder<TagTeamManager>
     */
    public function builder(): Builder
    {
        if (! isset($this->tagTeamId)) {
            throw new \Exception("You didn't specify a tag team");
        }

        return TagTeamManager::query()
            ->where('tag_team_id', $this->tagTeamId)
            ->whereNotNull('left_at')
            ->orderByDesc('hired_at');
    }

    public function configure(): void
    {
        $this->addAdditionalSelects([
            'tag_teams_managers.manager_id as manager_id',
        ]);
    }

    /**
     * Undocumented function
     *
     * @return array<int, Column>
     */
    public function columns(): array
    {
        return [
            Column::make(__('managers.full_name'), 'manager.full_name')
                ->searchable(),
            DateColumn::make(__('managers.date_hired'), 'hired_at')
                ->outputFormat('Y-m-d'),
            DateColumn::make(__('managers.date_fired'), 'left_at')
                ->outputFormat('Y-m-d'),
        ];
    }
}
