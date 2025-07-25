<?php

declare(strict_types=1);

namespace App\Livewire\Stables\Tables;

use App\Models\TagTeams\TagTeam;
use Carbon\Carbon;
use Exception;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Rappasoft\LaravelLivewireTables\DataTableComponent;
use Rappasoft\LaravelLivewireTables\Views\Column;
use Rappasoft\LaravelLivewireTables\Views\Columns\LinkColumn;

class PreviousTagTeams extends DataTableComponent
{
    protected string $resourceName = 'tag teams';

    protected string $databaseTableName = 'tag_teams';

    public ?int $stableId;

    /**
     * @return Builder<TagTeam>
     */
    public function builder(): Builder
    {
        if (! isset($this->stableId)) {
            throw new Exception("You didn't specify a stable");
        }

        return TagTeam::query()
            ->whereHas('stables', function (Builder $query) {
                $query->where('stable_id', $this->stableId)
                    ->whereNotNull('left_at');
            })
            ->with(['stables' => function (BelongsToMany $query) {
                $query->where('stable_id', $this->stableId)
                    ->whereNotNull('left_at')
                    ->withPivot(['joined_at', 'left_at']);
            }]);
    }

    /**
     * @return array<int, Column>
     */
    public function columns(): array
    {
        return [
            LinkColumn::make(__('tag-teams.name'))
                ->title(fn (TagTeam $row) => $row->name ?? 'Unknown')
                ->location(fn (TagTeam $row) => route('tag-teams.show', $row)),
            Column::make(__('stables.date_joined'))
                ->label(function (TagTeam $row): string {
                    $stable = $row->stables->first();
                    if (! $stable || ! isset($stable->pivot)) {
                        return '';
                    }

                    /** @var \App\Models\Stables\StableTagTeam $pivot */
                    $pivot = $stable->pivot;
                    $joinedAt = $pivot->getAttribute('joined_at');
                    if (! $joinedAt) {
                        return '';
                    }

                    return is_string($joinedAt) ?
                        Carbon::parse($joinedAt)->format('Y-m-d') :
                        $joinedAt->format('Y-m-d');
                }),
            Column::make(__('stables.date_left'))
                ->label(function (TagTeam $row): string {
                    $stable = $row->stables->first();
                    if (! $stable || ! isset($stable->pivot)) {
                        return '';
                    }

                    /** @var \App\Models\Stables\StableTagTeam $pivot */
                    $pivot = $stable->pivot;
                    $leftAt = $pivot->getAttribute('left_at');
                    if (! $leftAt) {
                        return '';
                    }

                    return is_string($leftAt) ?
                        Carbon::parse($leftAt)->format('Y-m-d') :
                        $leftAt->format('Y-m-d');
                }),
        ];
    }

    public function configure(): void
    {
        $this->setPrimaryKey('id')
            ->setColumnSelectDisabled()
            ->setSearchPlaceholder('Search '.$this->resourceName)
            ->setPaginationEnabled()
            ->setPerPageAccepted([5, 10, 25, 50, 100])
            ->setLoadingPlaceholderContent('Loading')
            ->setLoadingPlaceholderEnabled();
    }
}
