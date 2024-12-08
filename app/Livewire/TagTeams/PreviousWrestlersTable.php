<?php

declare(strict_types=1);

namespace App\Livewire\TagTeams;

use App\Livewire\Concerns\ShowTableTrait;
use App\Models\TagTeamPartner;
use Illuminate\Database\Eloquent\Builder;
use Rappasoft\LaravelLivewireTables\DataTableComponent;
use Rappasoft\LaravelLivewireTables\Views\Column;
use Rappasoft\LaravelLivewireTables\Views\Columns\DateColumn;

class PreviousWrestlersTable extends DataTableComponent
{
    use ShowTableTrait;

    protected string $databaseTableName = 'tag_team_wrestler';

    protected string $resourceName = 'wrestlers';

    public ?int $tagTeamId;

    public function builder(): Builder
    {
        if (! isset($this->tagTeamId)) {
            throw new \Exception("You didn't specify a tag team");
        }

        return TagTeamPartner::query()
            ->where('tag_team_id', $this->tagTeamId)
            ->whereNotNull('left_at')
            ->orderByDesc('joined_at');
    }

    public function configure(): void
    {
        $this->addAdditionalSelects([
            'tag_team_wrestler.wrestler_id as wrestler_id',
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
            Column::make(__('wrestlers.name'), 'wrestler.name'),
            DateColumn::make(__('tag-teams.date_joined'), 'joined_at')
                ->outputFormat('Y-m-d'),
            DateColumn::make(__('tag-teams.date_left'), 'left_at')
                ->outputFormat('Y-m-d'),
        ];
    }
}
