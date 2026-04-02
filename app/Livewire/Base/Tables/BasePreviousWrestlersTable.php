<?php

declare(strict_types=1);

namespace App\Livewire\Base\Tables;

use App\Livewire\Concerns\ShowTableTrait;
use App\Livewire\Table\Column;
use App\Livewire\Table\Columns\DateColumn;
use App\Livewire\Table\DataTableComponent;

abstract class BasePreviousWrestlersTable extends DataTableComponent
{
    use ShowTableTrait;

    protected string $resourceName = 'wrestlers';

    protected string $databaseTableName;

    public function configure(): void {}

    /**
     * Undocumented function
     *
     * @return array<int, Column>
     */
    public function columns(): array
    {
        return [
            Column::make(__('wrestlers.name'), 'member.name'),
            DateColumn::make(__('tag-teams.date_joined'), 'joined_at')
                ->outputFormat('Y-m-d'),
            DateColumn::make(__('tag-teams.date_left'), 'left_at')
                ->outputFormat('Y-m-d'),
        ];
    }
}
