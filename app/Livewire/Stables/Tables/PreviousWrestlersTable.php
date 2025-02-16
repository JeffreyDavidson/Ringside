<?php

declare(strict_types=1);

namespace App\Livewire\Stables\Tables;

use App\Livewire\Concerns\ShowTableTrait;
use App\Models\StableWrestler;
use Illuminate\Database\Eloquent\Builder;
use Rappasoft\LaravelLivewireTables\DataTableComponent;
use Rappasoft\LaravelLivewireTables\Views\Column;

class PreviousWrestlersTable extends DataTableComponent
{
    use ShowTableTrait;

    protected string $databaseTableName = 'stables_wrestlers';

    protected string $resourceName = 'wrestlers';

    public ?int $stableId;

    /**
     * @return Builder<StableWrestler>
     */
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
     * @return array<int, Column>
     **/
    public function columns(): array
    {
        return [
            Column::make(__('wrestlers.name'), 'wrestler_name'),
            Column::make(__('stables.date_joined'), 'date_joined'),
            Column::make(__('stables.date_left'), 'date_left'),
        ];
    }
}
