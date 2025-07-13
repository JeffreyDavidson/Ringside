<?php

declare(strict_types=1);

namespace App\Livewire\Titles\Tables;

use App\Livewire\Concerns\ShowTableTrait;
use App\Models\Titles\Title;
use App\Models\Titles\TitleChampionship;
use Exception;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Gate;
use Rappasoft\LaravelLivewireTables\DataTableComponent;
use Rappasoft\LaravelLivewireTables\Views\Column;

class PreviousTitleChampionshipsTable extends DataTableComponent
{
    use ShowTableTrait;

    protected string $databaseTableName = 'title_championships';

    protected string $resourceName = 'title championships';

    /**
     * Undocumented variable.
     */
    public ?int $titleId;

    public function configure(): void
    {
        Gate::authorize('viewList', Title::class);
    }

    /**
     * @return Builder<TitleChampionship>
     */
    public function builder(): Builder
    {
        if (! isset($this->titleId)) {
            throw new Exception("You didn't specify a title");
        }

        return TitleChampionship::query()
            ->where('title_id', $this->titleId)
            ->orderByDesc('lost_at');
    }

    /**
     * Undocumented function
     *
     * @return array<int, Column>
     */
    public function columns(): array
    {
        return [
            Column::make(__('championships.new_champion'), 'current_champion'),
            Column::make(__('championships.previous_champion'), 'former_champion'),
            Column::make(__('championships.dates_held'), 'dates_held'),
            Column::make(__('championships.days_held'), 'reign_length'),
        ];
    }
}
