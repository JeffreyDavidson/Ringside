<?php

declare(strict_types=1);

namespace App\Livewire\Titles\Tables;

use App\Actions\Titles\DebutAction;
use App\Actions\Titles\DeleteAction;
use App\Actions\Titles\PullAction;
use App\Actions\Titles\ReinstateAction;
use App\Actions\Titles\RestoreAction;
use App\Actions\Titles\RetireAction;
use App\Actions\Titles\UnretireAction;
use App\Builders\Titles\TitleBuilder;
use App\Livewire\Base\Tables\BaseTable;
use App\Livewire\Components\Tables\Columns\FirstActivityPeriodColumn;
use App\Livewire\Components\Tables\Filters\FirstActivityPeriodFilter;
use App\Livewire\Concerns\ExecutesSoftDeleteActions;
use App\Livewire\Concerns\ExecutesTitleActions;
use App\Livewire\Table\Column;
use App\Livewire\Table\Filter;
use App\Livewire\Table\Filters\SelectFilter;
use App\Models\Titles\Title;
use App\Queries\Titles\TitleChampionshipQuery;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Gate;

/** @extends BaseTable<Title> */
class Main extends BaseTable
{
    use ExecutesSoftDeleteActions;
    use ExecutesTitleActions;

    protected bool $showActionColumn = true;

    protected string $databaseTableName = 'titles';

    protected string $routeBasePath = 'titles';

    protected string $resourceName = 'titles';

    /** @return TitleBuilder<Title> */
    public function builder(): TitleBuilder
    {
        return Title::query()
            ->withActivityStatusState()
            ->with(['firstActivityPeriod', 'currentChampionship.champion'])
            ->oldest('name');
    }

    public function configure(): void
    {
        Gate::authorize('viewAny', Title::class);
    }

    /** @return array<int, Column> */
    public function columns(): array
    {
        return [
            Column::make(__('titles.name'), 'name')
                ->searchable(),
            Column::make(__('core.status'), 'status')
                ->label(fn (Title $row) => $row->status->label())
                ->excludeFromColumnSelect(),
            Column::make(__('titles.current_champion'), 'champion_name')
                ->label(fn (Title $row) => TitleChampionshipQuery::currentChampion($row)->name ?? 'Vacant'),
            FirstActivityPeriodColumn::make(__('activations.started_at')),
        ];
    }

    /** @return array<int, Filter> */
    public function filters(): array
    {
        return [
            SelectFilter::make('Status', 'status')
                ->options([
                    '' => 'All',
                    'undebuted' => 'Undebuted',
                    'active' => 'Active',
                    'inactive' => 'Inactive',
                    'with_pending_debut' => 'Pending Debut',
                ])
                ->filter(function (Builder $builder, string $value): void {
                    /** @var TitleBuilder<Title> $builder */
                    match ($value) {
                        'undebuted' => $builder->undebuted(),
                        'active' => $builder->active(),
                        'inactive' => $builder->inactive(),
                        'with_pending_debut' => $builder->withPendingDebut(),
                        default => null,
                    };
                }),
            FirstActivityPeriodFilter::make('Activation Date')->setFields('activityPeriods', 'activity_periods.started_at', 'activity_periods.ended_at'),
        ];
    }

    public function delete(Title $title): void
    {
        Gate::authorize('delete', $title);

        $this->executeSoftDeleteAction(function () use ($title): void {
            resolve(DeleteAction::class)->handle($title);
        }, 'Title successfully deleted.');
    }

    public function debut(Title $title): void
    {
        Gate::authorize('debut', $title);

        if ($this->executeTitleAction(function () use ($title): void {
            resolve(DebutAction::class)->handle($title);
        })) {
            $this->redirectRoute('titles.index');
        }
    }

    public function putOnHold(Title $title): void
    {
        Gate::authorize('pull', $title);

        if ($this->executeTitleAction(function () use ($title): void {
            resolve(PullAction::class)->handle($title);
        })) {
            $this->redirectRoute('titles.index');
        }
    }

    public function restore(int $titleId): void
    {
        $title = Title::onlyTrashed()->findOrFail($titleId);

        Gate::authorize('restore', $title);

        if ($this->executeTitleAction(function () use ($title): void {
            resolve(RestoreAction::class)->handle($title);
        })) {
            $this->redirectRoute('titles.index');
        }
    }

    public function retire(Title $title): void
    {
        Gate::authorize('retire', $title);

        if ($this->executeTitleAction(function () use ($title): void {
            resolve(RetireAction::class)->handle($title);
        })) {
            $this->redirectRoute('titles.index');
        }
    }

    public function unretire(Title $title): void
    {
        Gate::authorize('unretire', $title);

        if ($this->executeTitleAction(function () use ($title): void {
            resolve(UnretireAction::class)->handle($title);
        })) {
            $this->redirectRoute('titles.index');
        }
    }

    public function reinstate(Title $title): void
    {
        Gate::authorize('reinstate', $title);

        if ($this->executeTitleAction(function () use ($title): void {
            resolve(ReinstateAction::class)->handle($title);
        })) {
            $this->redirectRoute('titles.index');
        }
    }
}
