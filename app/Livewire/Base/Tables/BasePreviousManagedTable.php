<?php

declare(strict_types=1);

namespace App\Livewire\Base\Tables;

use App\Livewire\Concerns\ShowTableTrait;
use App\Livewire\Table\Column;
use App\Livewire\Table\Columns\DateColumn;
use App\Livewire\Table\DataTableComponent;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * Wrestlers or tag teams a manager previously managed: the managed name, searched through its
 * relationship, followed by the hired and fired dates of the assignment.
 *
 * @template TModel of Model
 *
 * @extends DataTableComponent<TModel>
 */
abstract class BasePreviousManagedTable extends DataTableComponent
{
    use ShowTableTrait;

    protected string $databaseTableName;

    /** Relationship on the assignment row that holds the managed record ("wrestler" or "tagTeam"). */
    protected string $managedRelation;

    /** Translation group of the managed name column ("wrestlers" or "tag-teams"). */
    protected string $managedLabelGroup;

    /** Translation key of the hired date column. */
    protected string $hiredLabelKey;

    /** Translation key of the fired date column. */
    protected string $firedLabelKey;

    /**
     * @return array<int, Column>
     */
    public function columns(): array
    {
        return [
            Column::make(__("{$this->managedLabelGroup}.name"), "{$this->managedRelation}.name")
                ->searchable(function (Builder $builder, string $searchTerm): void {
                    $builder->whereHas(
                        $this->managedRelation,
                        fn (Builder $managedQuery) => $managedQuery->whereLike(
                            'name',
                            '%'.mb_trim($searchTerm).'%',
                        ),
                    );
                }),
            DateColumn::make(__($this->hiredLabelKey), 'hired_at')
                ->outputFormat('Y-m-d'),
            DateColumn::make(__($this->firedLabelKey), 'fired_at')
                ->outputFormat('Y-m-d'),
        ];
    }
}
