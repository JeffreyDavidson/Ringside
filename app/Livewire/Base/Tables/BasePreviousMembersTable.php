<?php

declare(strict_types=1);

namespace App\Livewire\Base\Tables;

use App\Livewire\Concerns\ShowTableTrait;
use App\Livewire\Concerns\UsesRosterRouteResolver;
use App\Livewire\Table\Column;
use App\Livewire\Table\Columns\DateColumn;
use App\Livewire\Table\Columns\LinkColumn;
use App\Livewire\Table\DataTableComponent;
use App\Models\Roster\TagTeams\TagTeam;
use App\Models\Roster\Wrestlers\Wrestler;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * Past members of a group (a stable's wrestlers and tag teams, a tag team's wrestlers): a linked
 * member name that is searched through the member relationship, followed by the joined and left dates.
 *
 * @template TModel of Model
 *
 * @extends DataTableComponent<TModel>
 */
abstract class BasePreviousMembersTable extends DataTableComponent
{
    use ShowTableTrait;
    use UsesRosterRouteResolver;

    protected string $databaseTableName;

    /** Relationship on the membership row that holds the member ("wrestler" or "tagTeam"). */
    protected string $memberRelation;

    /** Translation group of the member's name column ("wrestlers" or "tag-teams"). */
    protected string $memberLabelGroup;

    /** Translation group of the joined and left date columns ("stables" or "tag-teams"). */
    protected string $dateLabelGroup;

    /**
     * @return array<int, Column>
     */
    public function columns(): array
    {
        return [
            LinkColumn::make(__("{$this->memberLabelGroup}.name"))
                ->title(fn (Model $row): string => $this->memberOf($row)->name ?? 'Unknown')
                ->location(function (Model $row): string {
                    $member = $this->memberOf($row);

                    return $member === null ? '#' : $this->routeResolver->urlFor($member);
                })
                ->searchable(function (Builder $builder, string $searchTerm): void {
                    $builder->whereHas(
                        $this->memberRelation,
                        fn (Builder $memberQuery) => $memberQuery->whereLike(
                            'name',
                            '%'.mb_trim($searchTerm).'%',
                        ),
                    );
                }),
            DateColumn::make(__("{$this->dateLabelGroup}.date_joined"), 'joined_at')
                ->outputFormat('Y-m-d'),
            DateColumn::make(__("{$this->dateLabelGroup}.date_left"), 'left_at')
                ->outputFormat('Y-m-d'),
        ];
    }

    private function memberOf(Model $row): Wrestler|TagTeam|null
    {
        $member = $row->getRelationValue($this->memberRelation);

        return $member instanceof Wrestler || $member instanceof TagTeam ? $member : null;
    }
}
