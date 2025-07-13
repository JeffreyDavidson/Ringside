<?php

declare(strict_types=1);

namespace App\Livewire\TagTeams\Tables;

use App\Livewire\Concerns\ShowTableTrait;
use App\Models\TagTeams\TagTeamWrestler;
use Exception;
use Illuminate\Database\Eloquent\Builder;
use Rappasoft\LaravelLivewireTables\DataTableComponent;
use Rappasoft\LaravelLivewireTables\Views\Column;
use Rappasoft\LaravelLivewireTables\Views\Columns\DateColumn;
use Rappasoft\LaravelLivewireTables\Views\Columns\LinkColumn;

class PreviousWrestlersTable extends DataTableComponent
{
    use ShowTableTrait;

    protected string $resourceName = 'wrestlers';

    protected string $databaseTableName = 'tag_teams_wrestlers';

    public ?int $tagTeamId;

    /**
     * @return Builder<TagTeamWrestler>
     */
    public function builder(): Builder
    {
        if (! isset($this->tagTeamId)) {
            throw new Exception("You didn't specify a tag team");
        }

        return TagTeamWrestler::query()
            ->with('wrestler')
            ->where('tag_teams_wrestlers.tag_team_id', $this->tagTeamId)
            ->whereNotNull('tag_teams_wrestlers.left_at')
            ->orderByDesc('tag_teams_wrestlers.joined_at');
    }

    /**
     * @return array<int, Column>
     */
    public function columns(): array
    {
        return [
            LinkColumn::make(__('wrestlers.name'))
                ->title(fn (TagTeamWrestler $row) => $row->wrestler?->name ?? 'Unknown')
                ->location(fn (TagTeamWrestler $row) => $row->wrestler ? route('wrestlers.show', $row->wrestler) : '#'),
            DateColumn::make(__('tag-teams.date_joined'), 'joined_at')
                ->outputFormat('Y-m-d'),
            DateColumn::make(__('tag-teams.date_left'), 'left_at')
                ->outputFormat('Y-m-d'),
        ];
    }

    public function configure(): void
    {
        // Removed additional selects that were causing SQL conflicts
        // The relationship handles member selection automatically
    }
}
