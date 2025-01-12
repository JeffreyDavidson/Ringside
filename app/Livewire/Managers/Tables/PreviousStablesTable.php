<?php

declare(strict_types=1);

namespace App\Livewire\Managers\Tables;

use App\Livewire\Concerns\ShowTableTrait;
use App\Models\StableManager;
use Illuminate\Database\Eloquent\Builder;
use Rappasoft\LaravelLivewireTables\DataTableComponent;
use Rappasoft\LaravelLivewireTables\Views\Column;
use Rappasoft\LaravelLivewireTables\Views\Columns\DateColumn;

class PreviousStablesTable extends DataTableComponent
{
    use ShowTableTrait;

    protected string $databaseTableName = 'stables_managers';

    protected string $resourceName = 'stables';

    /**
     * ManagerId to use for component.
     */
    public ?int $managerId;

    public function builder(): Builder
    {
        if (! isset($this->managerId)) {
            throw new \Exception("You didn't specify a manager");
        }

        return StableManager::query()
            ->where('manager_id', $this->managerId)
            ->whereNotNull('left_at')
            ->orderByDesc('hired_at');
    }

    public function configure(): void
    {
        $this->addAdditionalSelects([
            'stables_managers.stable_id as stable_id',
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
            Column::make(__('stables.name'), 'stable.name'),
            DateColumn::make(__('managers.date_hired'), 'hired_at')
                ->outputFormat('Y-m-d'),
            DateColumn::make(__('managers.date_fired'), 'left_at')
                ->outputFormat('Y-m-d'),
        ];
    }
}
