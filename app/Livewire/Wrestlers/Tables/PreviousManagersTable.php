<?php

declare(strict_types=1);

namespace App\Livewire\Wrestlers\Tables;

use App\Livewire\Concerns\ShowTableTrait;
use App\Models\WrestlerManager;
use Exception;
use Illuminate\Database\Eloquent\Builder;
use Rappasoft\LaravelLivewireTables\DataTableComponent;
use Rappasoft\LaravelLivewireTables\Views\Column;
use Rappasoft\LaravelLivewireTables\Views\Columns\DateColumn;

class PreviousManagersTable extends DataTableComponent
{
    use ShowTableTrait;

    /**
     * Wrestler to use for component.
     */
    public ?int $wrestlerId;

    protected string $databaseTableName = 'wrestlers_managers';

    protected string $resourceName = 'managers';

    /**
     * @return Builder<WrestlerManager>
     */
    public function builder(): Builder
    {
        if (! isset($this->wrestlerId)) {
            throw new Exception("You didn't specify a wrestler");
        }

        return WrestlerManager::query()
            ->where('wrestler_id', $this->wrestlerId)
            ->whereNotNull('left_at')
            ->orderByDesc('hired_at');
    }

    public function configure(): void
    {
        $this->addAdditionalSelects([
            'wrestlers_managers.manager_id as manager_id',
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
            Column::make(__('managers.full_name'), 'manager.full_name')
                ->searchable(),
            DateColumn::make(__('managers.date_hired'), 'hired_at')
                ->outputFormat('Y-m-d'),
            DateColumn::make(__('managers.date_fired'), 'left_at')
                ->outputFormat('Y-m-d'),
        ];
    }
}
