<?php

declare(strict_types=1);

namespace App\Livewire\Stables;

use App\Livewire\Concerns\ShowTableTrait;
use App\Models\StableManager;
use Illuminate\Database\Eloquent\Builder;
use Rappasoft\LaravelLivewireTables\DataTableComponent;
use Rappasoft\LaravelLivewireTables\Views\Column;
use Rappasoft\LaravelLivewireTables\Views\Columns\DateColumn;

class PreviousManagersTable extends DataTableComponent
{
    use ShowTableTrait;

    protected string $databaseTableName = 'stables_managers';

    protected string $resourceName = 'managers';

    public ?int $stableId;

    public function builder(): Builder
    {
        if (! isset($this->stableId)) {
            throw new \Exception("You didn't specify a stable");
        }

        return StableManager::query()
            ->where('stable_id', $this->stableId)
            ->whereNotNull('left_at')
            ->orderByDesc('hired_at');
    }

    public function configure(): void
    {
        $this->addAdditionalSelects([
            'stables_managers.manager_id as manager_id',
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
            Column::make(__('managers.full_name'), 'manager.full_name'),
            DateColumn::make(__('managers.date_hired'), 'hired_at')
                ->outputFormat('Y-m-d'),
            DateColumn::make(__('managers.date_fired'), 'left_at')
                ->outputFormat('Y-m-d'),
        ];
    }
}
