<?php

declare(strict_types=1);

namespace App\Livewire\Base\Tables;

use App\Livewire\Concerns\ShowTableTrait;
use App\Models\TagTeam;
use App\Models\TagTeamPartner;
use Illuminate\Database\Eloquent\Model;
use Rappasoft\LaravelLivewireTables\DataTableComponent;
use Rappasoft\LaravelLivewireTables\Views\Column;
use Rappasoft\LaravelLivewireTables\Views\Columns\DateColumn;
use Rappasoft\LaravelLivewireTables\Views\Columns\LinkColumn;

abstract class BasePreviousTagTeamsTable extends DataTableComponent
{
    use ShowTableTrait;

    protected string $resourceName = 'tag teams';

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
            LinkColumn::make(__('tag-teams.name'))
                ->title(fn (Model $row) => $row->tagTeam->name)
                ->location(fn (Model $row) => route('tag-teams.show', $row)),
            // LinkColumn::make(__('tag-teams.partner'))
            //     ->title(fn (TagTeamPartner $row) => $row->partner->name)
            //     ->location(fn (TagTeam $row) => route('wrestlers.show', $row)),
            DateColumn::make(__('stables.date_joined'), 'joined_at')
                ->outputFormat('Y-m-d'),
            DateColumn::make(__('stables.date_left'), 'left_at')
                ->outputFormat('Y-m-d'),
        ];
    }
}
