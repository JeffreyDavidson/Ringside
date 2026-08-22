<?php

declare(strict_types=1);

namespace App\Livewire\Managers\Tables;

use App\Livewire\Concerns\ShowTableTrait;
use App\Livewire\Table\Column;
use App\Livewire\Table\Columns\DateColumn;
use App\Livewire\Table\DataTableComponent;
use App\Models\Roster\Wrestlers\WrestlerManager;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Attributes\Locked;
use LogicException;

/** @extends DataTableComponent<WrestlerManager> */
class PreviousWrestlers extends DataTableComponent
{
    use ShowTableTrait;

    #[Locked]
    public ?int $managerId;

    protected string $databaseTableName = 'wrestlers_managers';

    protected string $resourceName = 'wrestlers';

    /**
     * @return Builder<WrestlerManager>
     */
    public function builder(): Builder
    {
        if (! isset($this->managerId)) {
            throw new LogicException('A manager was not provided.');
        }

        return WrestlerManager::query()
            ->forManagerId($this->managerId)
            ->ended()
            ->mostRecentlyHiredFirst();
    }

    protected function configure(): void
    {
        $this->addAdditionalSelects([
            'wrestlers_managers.wrestler_id as wrestler_id',
        ]);
    }

    /**
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
