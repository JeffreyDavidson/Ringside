<?php

declare(strict_types=1);

namespace App\Livewire\Concerns;

use App\Builders\Matches\EventMatchBuilder;
use App\Livewire\Table\Column;
use App\Models\Matches\EventMatch;
use App\Models\Matches\MatchCompetitor;
use App\Models\Roster\TagTeams\TagTeam;
use App\Models\Roster\Wrestlers\Wrestler;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Url;

trait FiltersMatchHistory
{
    /** URL and Livewire filter state is untrusted and may be malformed. */
    #[Url(as: 'opponent', except: '')]
    public mixed $opponent = '';

    private Wrestler|TagTeam|null $historyContext = null;

    abstract protected function historySubject(): Wrestler|TagTeam;

    private function subject(): Wrestler|TagTeam
    {
        return $this->historyContext ??= $this->historySubject();
    }

    protected function configureHistory(): void
    {
        $this->beforeWrapperView = 'matches.history-opponent-filter';
    }

    /** @return EventMatchBuilder<EventMatch> */
    protected function historyForSubject(): EventMatchBuilder
    {
        $subject = $this->subject();
        Gate::authorize('view', $subject);
        $query = EventMatch::query()->forHistory()->forCompetitor($subject)->withRecordedResultFlag();

        if ($subject->promotion_id === null) {
            return $query->whereKey([]);
        }

        $query->forPromotionHistory($subject->promotion_id, now());

        if ($this->opponent === '') {
            return $query;
        }

        $opponent = $this->resolveOpponent();

        return $opponent === null ? $query->whereKey([]) : $query->opposedTo($subject, $opponent);
    }

    private function resolveOpponent(): Wrestler|TagTeam|null
    {
        $subject = $this->subject();
        Gate::authorize('view', $subject);

        if (! is_string($this->opponent) || ! preg_match('/^(wrestler|tag_team):([1-9][0-9]{0,9})$/D', $this->opponent, $matches)) {
            return null;
        }

        $class = $matches[1] === 'wrestler' ? Wrestler::class : TagTeam::class;

        return $class::query()->withTrashed()->where('promotion_id', $subject->promotion_id)->find((int) $matches[2]);
    }

    #[Computed]
    public function opponentLabel(): string
    {
        $opponent = $this->resolveOpponent();

        return $opponent === null ? __('matches.history.opponent_unavailable') : $opponent->name;
    }

    public function updatedOpponent(): void
    {
        $this->forgetMetadata();
        $this->resetPage();
    }

    public function clearOpponent(): void
    {
        Gate::authorize('view', $this->subject());
        $this->opponent = '';
        $this->updatedOpponent();
    }

    public function clearFilters(): void
    {
        $this->clearOpponent();
        parent::clearFilters();
    }

    /** @return array<int, Column> */
    public function columns(): array
    {
        $columns = parent::columns();
        $columns[3] = Column::make(__('matches.competitors'))
            ->label(fn (EventMatch $match): string => $this->matchTableFormatter->historyCompetitorLinks($match))->html();
        $columns[5] = Column::make(__('matches.result'))
            ->label(fn (EventMatch $match): string => $this->matchTableFormatter->historyResult($match))->html();

        return [...$columns, Column::make(__('matches.history.opponent'))
            ->label(fn (EventMatch $match): string => $this->opposingHistoryLinks($match))->html()];
    }

    public function render(): View
    {
        return parent::render()->with('stackRowsOnMobile', true)
            ->with('hasActiveFilters', $this->opponent !== '' || $this->search !== '')
            ->with('historyUnavailable', $this->subject()->promotion_id === null);
    }

    private function opposingHistoryLinks(EventMatch $match): string
    {
        $subject = $this->subject();
        $entry = $match->competitors->sole(fn (MatchCompetitor $entry): bool => $entry->competitor_type === $subject->getMorphClass() && $entry->competitor_id === $subject->id);

        return $match->competitors->where('match_side_id', '!=', $entry->match_side_id)
            ->map(function (MatchCompetitor $entry) use ($subject): string {
                $opponent = $entry->getRelation('competitor');

                if ((! $opponent instanceof Wrestler && ! $opponent instanceof TagTeam) || $opponent->promotion_id !== $subject->promotion_id) {
                    return e(__('matches.history.record_unavailable'));
                }

                $url = route($subject instanceof Wrestler ? 'wrestlers.show' : 'tag-teams.show', [
                    $subject, 'opponent' => $opponent->getMorphClass().':'.$opponent->id,
                ]);

                return '<a href="'.e($url).'">'.e($opponent->name).'</a>';
            })->join(', ');
    }
}
