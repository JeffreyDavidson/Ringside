<?php

declare(strict_types=1);

namespace App\Livewire\Stables\Tables;

use App\Livewire\Concerns\ShowTableTrait;
use App\Models\StableManager;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Rappasoft\LaravelLivewireTables\DataTableComponent;
use Rappasoft\LaravelLivewireTables\Views\Column;
use Rappasoft\LaravelLivewireTables\Views\Columns\DateColumn;
use Rappasoft\LaravelLivewireTables\Views\Columns\LinkColumn;

class PreviousManagersTable extends DataTableComponent
{
    use ShowTableTrait;

    protected string $databaseTableName = 'stables_managers';

    protected string $resourceName = 'managers';

    public ?int $stableId;

    /**
     * @return Builder<StableManager>
     */
    public function builder(): Builder
    {
        if (! isset($this->stableId)) {
            throw new \Exception("You didn't specify a stable");
        }

        return StableManager::query()
            ->with(['manager'])
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
            LinkColumn::make(__('managers.name'))
                ->title(fn (Model $row) => $row->manager->full_name)
                ->location(fn (Model $row) => route('managers.show', $row)),
            DateColumn::make(__('managers.date_hired'), 'hired_at')
                ->outputFormat('Y-m-d'),
            DateColumn::make(__('managers.date_fired'), 'left_at')
                ->outputFormat('Y-m-d'),
        ];
    }
}
