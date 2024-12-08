<?php

declare(strict_types=1);

namespace App\Livewire\Stables;

use App\Livewire\Concerns\ShowTableTrait;
use App\Models\StableWrestler;
use Illuminate\Database\Eloquent\Builder;
use Rappasoft\LaravelLivewireTables\DataTableComponent;
use Rappasoft\LaravelLivewireTables\Views\Column;
use Rappasoft\LaravelLivewireTables\Views\Columns\DateColumn;

class PreviousWrestlersTable extends DataTableComponent
{
    use ShowTableTrait;

    protected string $databaseTableName = 'stables_wrestlers';

    protected string $resourceName = 'wrestlers';

    public ?int $stableId;

    public function builder(): Builder
    {
        if (! isset($this->stableId)) {
            throw new \Exception("You didn't specify a stable");
        }

        return StableWrestler::query()
            ->where('stable_id', $this->stableId)
            ->whereNotNull('left_at')
            ->orderByDesc('joined_at');
    }

    public function configure(): void
    {
        $this->addAdditionalSelects([
            'stables_wrestlers.wrestler_id as wrestler_id',
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
            DateColumn::make(__('stables.date_joined'), 'joined_at')
                ->outputFormat('Y-m-d'),
            DateColumn::make(__('stables.date_left'), 'left_at')
                ->outputFormat('Y-m-d'),
        ];
    }
}
