<?php

declare(strict_types=1);

namespace App\Livewire\Stables\Tables;

use App\Models\Wrestlers\Wrestler;
use Exception;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Rappasoft\LaravelLivewireTables\DataTableComponent;
use Rappasoft\LaravelLivewireTables\Views\Column;
use Rappasoft\LaravelLivewireTables\Views\Columns\DateColumn;
use Rappasoft\LaravelLivewireTables\Views\Columns\LinkColumn;

class PreviousWrestlersTable extends DataTableComponent
{

    protected string $resourceName = 'wrestlers';

    protected string $databaseTableName = 'wrestlers';

    public ?int $stableId;

    /**
     * @return Builder<Wrestler>
     */
    public function builder(): Builder
    {
        if (! isset($this->stableId)) {
            throw new Exception("You didn't specify a stable");
        }

        return Wrestler::query()
            ->whereHas('stables', function (Builder $query) {
                $query->where('stable_id', $this->stableId)
                      ->whereNotNull('left_at');
            })
            ->with(['stables' => function (\Illuminate\Database\Eloquent\Relations\BelongsToMany $query) {
                $query->where('stable_id', $this->stableId)
                      ->whereNotNull('left_at')
                      ->withPivot(['joined_at', 'left_at']);
            }]);
    }

    /**
     * @return array<int, Column>
     */
    public function columns(): array
    {
        return [
            LinkColumn::make(__('wrestlers.name'))
                ->title(fn (Wrestler $row) => $row->name ?? 'Unknown')
                ->location(fn (Wrestler $row) => route('wrestlers.show', $row)),
            Column::make(__('stables.date_joined'))
                ->label(fn (Wrestler $row) => $row->stables->first()?->pivot?->joined_at ?
                    (is_string($row->stables->first()->pivot->joined_at) ?
                        \Carbon\Carbon::parse($row->stables->first()->pivot->joined_at)->format('Y-m-d') :
                        $row->stables->first()->pivot->joined_at->format('Y-m-d')
                    ) : ''),
            Column::make(__('stables.date_left'))
                ->label(fn (Wrestler $row) => $row->stables->first()?->pivot?->left_at ?
                    (is_string($row->stables->first()->pivot->left_at) ?
                        \Carbon\Carbon::parse($row->stables->first()->pivot->left_at)->format('Y-m-d') :
                        $row->stables->first()->pivot->left_at->format('Y-m-d')
                    ) : ''),
        ];
    }

    public function configure(): void
    {
        $this->setPrimaryKey('id')
            ->setColumnSelectDisabled()
            ->setSearchPlaceholder('Search '.$this->resourceName)
            ->setPaginationEnabled()
            ->setPerPageAccepted([5, 10, 25, 50, 100])
            ->setLoadingPlaceholderContent('Loading')
            ->setLoadingPlaceholderEnabled();
    }

}
