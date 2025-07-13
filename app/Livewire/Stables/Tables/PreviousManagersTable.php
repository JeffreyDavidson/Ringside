<?php

declare(strict_types=1);

namespace App\Livewire\Stables\Tables;

use App\Livewire\Base\Tables\BasePreviousManagersTable;
use App\Models\Managers\Manager;
use App\Models\Stables\Stable;
use App\Repositories\StableRepository;
use Exception;
use Illuminate\Database\Eloquent\Builder;
use Rappasoft\LaravelLivewireTables\Views\Column;

class PreviousManagersTable extends BasePreviousManagersTable
{
    public string $databaseTableName = 'managers';

    public ?int $stableId;

    /**
     * @return Builder<Manager>
     */
    public function builder(): Builder
    {
        if (! isset($this->stableId)) {
            throw new Exception("You didn't specify a stable");
        }

        // Note: Stables do not directly have managers.
        // Managers are associated with individual wrestlers and tag teams.
        // This table would show managers who previously managed members of this stable.
        // For now, return empty query since this is not a valid business relationship.
        return Manager::query()->whereRaw('1 = 0'); // Empty result set
    }

    public function configure(): void
    {
        // No additional selects needed for direct manager query
    }

    /**
     * @return array<int, Column>
     */
    public function columns(): array
    {
        return [
            Column::make(__('managers.name'), 'full_name')
                ->searchable(),
            Column::make(__('managers.status'), 'status')
                ->searchable(),
        ];
    }
}
