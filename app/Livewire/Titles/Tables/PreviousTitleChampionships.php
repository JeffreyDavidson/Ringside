<?php

declare(strict_types=1);

namespace App\Livewire\Titles\Tables;

use App\Builders\Titles\TitleChampionshipBuilder;
use App\Livewire\Concerns\ShowTableTrait;
use App\Livewire\Table\Column;
use App\Livewire\Table\Columns\LinkColumn;
use App\Livewire\Table\DataTableComponent;
use App\Models\Roster\TagTeams\TagTeam;
use App\Models\Roster\Wrestlers\Wrestler;
use App\Models\Titles\Title;
use App\Models\Titles\TitleChampionship;
use App\Queries\Titles\TitleChampionshipQuery;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Locked;
use LogicException;

/** @extends DataTableComponent<TitleChampionship> */
class PreviousTitleChampionships extends DataTableComponent
{
    use ShowTableTrait;

    protected string $databaseTableName = 'titles_championships';

    protected string $resourceName = 'title championships';

    /**
     * Undocumented variable.
     */
    #[Locked]
    public ?int $titleId;

    protected function configure(): void
    {
        Gate::authorize('viewAny', Title::class);
    }

    /** @return TitleChampionshipBuilder<TitleChampionship> */
    public function builder(): TitleChampionshipBuilder
    {
        if (! isset($this->titleId)) {
            throw new LogicException('A title was not provided.');
        }

        return TitleChampionship::query()
            ->forTitleId($this->titleId)
            ->forPreviousHistory()
            ->with('champion');
    }

    /**
     * @return array<int, Column>
     */
    public function columns(): array
    {
        return [
            LinkColumn::make(__('championships.new_champion'))
                ->title(fn (TitleChampionship $row): string => $row->champion->name)
                ->location(fn (TitleChampionship $row): string => $this->championLocation($row->champion)),
            LinkColumn::make(__('championships.previous_champion'))
                ->title(fn (TitleChampionship $row): string => $row->previousChampionship?->champion->name ?? 'N/A')
                ->location(fn (TitleChampionship $row): ?string => $this->previousChampionLocation($row)),
            Column::make(__('championships.dates_held'))
                ->label(fn (TitleChampionship $row): string => $this->datesHeld($row)),
            Column::make(__('championships.days_held'))
                ->label(fn (TitleChampionship $row): int => TitleChampionshipQuery::reignLengthInDays($row)),
        ];
    }

    private function championLocation(Wrestler|TagTeam $champion): string
    {
        return match (true) {
            $champion instanceof Wrestler => route('wrestlers.show', $champion),
            $champion instanceof TagTeam => route('tag-teams.show', $champion),
        };
    }

    private function datesHeld(TitleChampionship $championship): string
    {
        $wonAt = $championship->won_at->toDateString();
        $lostAt = $championship->lost_at?->toDateString();

        return $lostAt === null ? $wonAt : "{$wonAt} - {$lostAt}";
    }

    private function previousChampionLocation(TitleChampionship $championship): ?string
    {
        $champion = $championship->previousChampionship?->champion;

        return $champion === null ? null : $this->championLocation($champion);
    }
}
