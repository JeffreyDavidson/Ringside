<?php

declare(strict_types=1);

namespace App\Livewire\Managers\Tables;

use App\Livewire\Concerns\ShowTableTrait;
use App\Models\Wrestlers\WrestlerManager;
use Exception;
use Illuminate\Database\Eloquent\Builder;
use Rappasoft\LaravelLivewireTables\DataTableComponent;
use Rappasoft\LaravelLivewireTables\Views\Column;
use Rappasoft\LaravelLivewireTables\Views\Columns\DateColumn;

class PreviousWrestlers extends DataTableComponent
{
    use ShowTableTrait;

    public ?int $managerId;

    protected string $databaseTableName = 'wrestlers_managers';

    protected string $resourceName = 'wrestlers';

    /**
     * @return Builder<WrestlerManager>
     */
    public function builder(): Builder
    {
        if (! isset($this->managerId)) {
            throw new Exception("You didn't specify a manager");
        }

        return WrestlerManager::query()
            ->where('manager_id', $this->managerId)
            ->whereNotNull('fired_at')
            ->orderByDesc('hired_at');
    }

    public function configure(): void
    {
        $this->addAdditionalSelects([
            'wrestlers_managers.wrestler_id as wrestler_id',
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
            DateColumn::make(__('wrestlers.date_hired'), 'hired_at')
                ->outputFormat('Y-m-d'),
            DateColumn::make(__('wrestlers.date_left'), 'fired_at')
                ->outputFormat('Y-m-d'),
        ];
    }
}
