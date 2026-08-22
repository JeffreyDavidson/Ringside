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
use App\Enums\Titles\TitleStatus;
use App\Enums\Titles\TitleType;
use App\Livewire\Base\Tables\BaseTable;
use App\Livewire\Components\Tables\Columns\FirstActivityPeriodColumn;
use App\Livewire\Components\Tables\Filters\FirstActivityPeriodFilter;
use App\Livewire\Concerns\ExecutesBusinessActions;
use App\Livewire\Table\Column;
use App\Livewire\Table\Filter;
use App\Livewire\Table\Filters\SelectFilter;
use App\Models\Titles\Title;
use App\Queries\Titles\TitleChampionshipQuery;
use Illuminate\Support\Facades\Gate;

/** @extends BaseTable<Title> */
class Main extends BaseTable
{
    use ExecutesBusinessActions;

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

    protected function configure(): void
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
                ->options(TitleStatus::filterOptions())
                ->filter(function (TitleBuilder $builder, string $value): void {
                    $status = TitleStatus::tryFrom($value);

                    if ($status !== null) {
                        $builder->whereStatus($status);
                    }
                }),
            SelectFilter::make('Type', 'type')
                ->options([
                    '' => 'All',
                    TitleType::Singles->value => TitleType::Singles->label(),
                    TitleType::TagTeam->value => TitleType::TagTeam->label(),
                ])
                ->filter(function (TitleBuilder $builder, string $value): void {
                    $type = TitleType::tryFrom($value);

                    if ($type !== null) {
                        $builder->whereType($type);
                    }
                }),
            FirstActivityPeriodFilter::make('Activation Date')->setFields('activityPeriods', 'activity_periods.started_at', 'activity_periods.ended_at'),
        ];
    }

    public function delete(Title $title): void
    {
        Gate::authorize('delete', $title);

        $this->executeBusinessAction(function () use ($title): void {
            resolve(DeleteAction::class)->handle($title);
        }, __('titles.actions.deleted'));
    }

    public function debut(Title $title): void
    {
        Gate::authorize('debut', $title);

        if ($this->executeBusinessAction(function () use ($title): void {
            resolve(DebutAction::class)->handle($title);
        })) {
            $this->redirectRoute('titles.index');
        }
    }

    public function putOnHold(Title $title): void
    {
        Gate::authorize('pull', $title);

        if ($this->executeBusinessAction(function () use ($title): void {
            resolve(PullAction::class)->handle($title);
        })) {
            $this->redirectRoute('titles.index');
        }
    }

    public function restore(int $titleId): void
    {
        $title = Title::onlyTrashed()->findOrFail($titleId);

        Gate::authorize('restore', $title);

        if ($this->executeBusinessAction(function () use ($title): void {
            resolve(RestoreAction::class)->handle($title);
        })) {
            $this->redirectRoute('titles.index');
        }
    }

    public function retire(Title $title): void
    {
        Gate::authorize('retire', $title);

        if ($this->executeBusinessAction(function () use ($title): void {
            resolve(RetireAction::class)->handle($title);
        })) {
            $this->redirectRoute('titles.index');
        }
    }

    public function unretire(Title $title): void
    {
        Gate::authorize('unretire', $title);

        if ($this->executeBusinessAction(function () use ($title): void {
            resolve(UnretireAction::class)->handle($title);
        })) {
            $this->redirectRoute('titles.index');
        }
    }

    public function reinstate(Title $title): void
    {
        Gate::authorize('reinstate', $title);

        if ($this->executeBusinessAction(function () use ($title): void {
            resolve(ReinstateAction::class)->handle($title);
        })) {
            $this->redirectRoute('titles.index');
        }
    }
}
