<?php

declare(strict_types=1);

namespace App\Livewire\Titles\Tables;

use App\Builders\Titles\TitleChampionshipBuilder;
use App\Livewire\Concerns\ShowTableTrait;
use App\Livewire\Concerns\UsesRosterRouteResolver;
use App\Livewire\Table\Column;
use App\Livewire\Table\Columns\LinkColumn;
use App\Livewire\Table\DataTableComponent;
use App\Models\Roster\TagTeams\TagTeam;
use App\Models\Roster\Wrestlers\Wrestler;
use App\Models\Titles\Title;
use App\Models\Titles\TitleChampionship;
use App\Queries\Titles\TitleChampionshipQuery;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Attributes\Locked;

/** @extends DataTableComponent<TitleChampionship> */
class TitleHistory extends DataTableComponent
{
    use ShowTableTrait;
    use UsesRosterRouteResolver;

    protected string $databaseTableName = 'titles_championships';

    #[\Override]
    protected string $resourceName = 'title reigns';

    /**
     * Title whose reigns are listed.
     */
    #[Locked]
    public ?int $titleId = null;

    protected function configure(): void
    {
        $this->authorizeContextRecord(Title::class, $this->titleId, 'title');
    }

    /** @return TitleChampionshipBuilder<TitleChampionship> */
    public function builder(): TitleChampionshipBuilder
    {
        $titleId = $this->requireContextId($this->titleId ?? null, 'title');

        return TitleChampionship::query()
            ->forTitleId($titleId)
            ->mostRecentlyWonFirst()
            ->with(['champion', 'title.promotion']);
    }

    /**
     * @return array<int, Column>
     */
    public function columns(): array
    {
        return [
            LinkColumn::make(__('championships.champion'))
                ->title(fn (TitleChampionship $row): string => $row->champion->name)
                ->location(fn (TitleChampionship $row): ?string => $row->champion->trashed()
                    ? null
                    : $this->routeResolver->urlFor($row->champion))
                ->searchable(function (TitleChampionshipBuilder $builder, string $searchTerm): void {
                    $builder->whereHasMorph(
                        'champion',
                        [Wrestler::class, TagTeam::class],
                        fn (Builder $championQuery) => $championQuery->withTrashed()->whereLike(
                            'name',
                            '%'.mb_trim($searchTerm).'%',
                        ),
                    );
                }),
            Column::make(__('championships.dates_held'))
                ->label(fn (TitleChampionship $row): string => $this->datesHeld($row)),
            Column::make(__('championships.days_held'))
                ->label(fn (TitleChampionship $row): int => TitleChampionshipQuery::reignLengthInDays($row)),
        ];
    }

    private function datesHeld(TitleChampionship $championship): string
    {
        $wonAt = $championship->local_won_at->toDateString();
        $lostAt = $championship->local_lost_at?->toDateString() ?? __('championships.current');

        return "{$wonAt} - {$lostAt}";
    }
}
