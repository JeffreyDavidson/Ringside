<?php

declare(strict_types=1);

namespace App\Livewire\Stables\Tables;

use App\Livewire\Concerns\ShowTableTrait;
use App\Livewire\Table\Column;
use App\Livewire\Table\Columns\DateColumn;
use App\Livewire\Table\Columns\LinkColumn;
use App\Livewire\Table\DataTableComponent;
use App\Models\Roster\Stables\StableWrestler;
use Illuminate\Database\Eloquent\Builder;
use LogicException;

/** @extends DataTableComponent<StableWrestler> */
class PreviousWrestlers extends DataTableComponent
{
    use ShowTableTrait;

    protected string $resourceName = 'wrestlers';

    protected string $databaseTableName = 'stables_wrestlers';

    public ?int $stableId;

    /**
     * @return Builder<StableWrestler>
     */
    public function builder(): Builder
    {
        if (! isset($this->stableId)) {
            throw new LogicException('A stable was not provided.');
        }

        return StableWrestler::query()
            ->with('wrestler')
            ->forStableId($this->stableId)
            ->ended()
            ->mostRecentlyJoinedFirst();
    }

    /**
     * @return array<int, Column>
     */
    public function columns(): array
    {
        return [
            LinkColumn::make(__('wrestlers.name'))
                ->title(fn (StableWrestler $row) => $row->wrestler->name ?? 'Unknown')
                ->location(fn (StableWrestler $row) => $row->wrestler ? route('wrestlers.show', $row->wrestler) : '#'),
            DateColumn::make(__('stables.date_joined'), 'joined_at')
                ->outputFormat('Y-m-d'),
            DateColumn::make(__('stables.date_left'), 'left_at')
                ->outputFormat('Y-m-d'),
        ];
    }

    public function configure(): void
    {
        $this->addAdditionalSelects([
            'stables_wrestlers.wrestler_id',
            'stables_wrestlers.stable_id',
            'stables_wrestlers.joined_at',
            'stables_wrestlers.left_at',
        ]);
    }
}
