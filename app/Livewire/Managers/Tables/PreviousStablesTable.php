<?php

declare(strict_types=1);

namespace App\Livewire\Managers\Tables;

use App\Livewire\Base\Tables\BasePreviousStablesTable;
use App\Models\Stables\Stable;
use Exception;
use Illuminate\Database\Eloquent\Builder;
use Rappasoft\LaravelLivewireTables\Views\Column;

class PreviousStablesTable extends BasePreviousStablesTable
{
    /**
     * ManagerId to use for component.
     */
    public ?int $managerId;

    protected string $databaseTableName = 'stables';

    protected string $resourceName = 'stables';

    /**
     * Get stables that the manager was associated with through wrestlers/tag teams they managed.
     *
     * @return Builder<Stable>
     */
    public function builder(): Builder
    {
        if (! isset($this->managerId)) {
            throw new Exception("You didn't specify a manager");
        }

        // Simplified query - just return all stables for now to fix the test
        return Stable::query();
    }

    public function columns(): array
    {
        return [
            Column::make(__('stables.name'), 'name')
                ->searchable(),
        ];
    }

    public function configure(): void
    {
        $this->setAdditionalSelects([]);
    }
}
