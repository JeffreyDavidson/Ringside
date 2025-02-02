<?php

declare(strict_types=1);

namespace App\Livewire\Wrestlers\Tables;

use App\Livewire\Concerns\ShowTableTrait;
use App\Models\TagTeam;
use App\Models\TagTeamPartner;
use Illuminate\Database\Eloquent\Builder;
use Rappasoft\LaravelLivewireTables\DataTableComponent;
use Rappasoft\LaravelLivewireTables\Views\Columns\DateColumn;
use Rappasoft\LaravelLivewireTables\Views\Columns\LinkColumn;

class PreviousTagTeamsTable extends DataTableComponent
{
    use ShowTableTrait;

    protected string $databaseTableName = 'tag_teams';

    protected string $resourceName = 'tag teams';

    /**
     * Wrestler to use for component.
     */
    public ?int $wrestlerId;

    /**
     * @return Builder<TagTeamPartner>
     */
    public function builder(): Builder
    {
        if (! isset($this->wrestlerId)) {
            throw new \Exception("You didn't specify a wrestler");
        }

        return TagTeamPartner::query()
            ->where('wrestler_id', $this->wrestlerId)
            ->whereNotNull('left_at')
            ->orderByDesc('joined_at');
    }

    public function configure(): void {}

    /**
     * @return array<int, LinkColumn|DateColumn>
     **/
    public function columns(): array
    {
        return [
            LinkColumn::make(__('tag-teams.name'))
                ->title(fn (TagTeam $row) => $row->name)
                ->location(fn ($row) => route('tag-teams.show', $row)),
            LinkColumn::make(__('tag-teams.partner'))
                ->title(fn (TagTeamPartner $row) => $row->partner->name)
                ->location(fn (TagTeam $row) => route('wrestlers.show', $row)),
            DateColumn::make(__('tag-teams.date_joined'), 'date_joined')
                ->outputFormat('Y-m-d H:i'),
            DateColumn::make(__('tag-teams.date_left'), 'date_left')
                ->outputFormat('Y-m-d H:i'),
        ];
    }
}
