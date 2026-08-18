<?php

declare(strict_types=1);

namespace App\Livewire\Referees\Tables;

use App\Actions\Referees\DeleteAction;
use App\Actions\Referees\EmployAction;
use App\Actions\Referees\HealAction;
use App\Actions\Referees\InjureAction;
use App\Actions\Referees\ReinstateAction;
use App\Actions\Referees\ReleaseAction;
use App\Actions\Referees\RestoreAction;
use App\Actions\Referees\RetireAction;
use App\Actions\Referees\SuspendAction;
use App\Actions\Referees\UnretireAction;
use App\Builders\Roster\RefereeBuilder;
use App\Enums\Roster\RosterEntityType;
use App\Livewire\Base\Tables\BaseTable;
use App\Livewire\Components\Tables\Columns\FirstEmploymentDateColumn;
use App\Livewire\Components\Tables\Filters\FirstEmploymentFilter;
use App\Livewire\Concerns\ExecutesRosterActions;
use App\Livewire\Table\Column;
use App\Livewire\Table\Filter;
use App\Livewire\Table\Filters\SelectFilter;
use App\Models\Roster\Referees\Referee;
use Illuminate\Support\Facades\Gate;

/** @extends BaseTable<Referee> */
class Main extends BaseTable
{
    use ExecutesRosterActions;

    protected bool $showActionColumn = true;

    protected string $databaseTableName = 'referees';

    protected string $routeBasePath = 'referees';

    protected string $resourceName = 'referees';

    /** @return RefereeBuilder<Referee> */
    public function builder(): RefereeBuilder
    {
        return Referee::query()
            ->withEmploymentStatusState()
            ->with('firstEmployment')
            ->oldest('last_name');
    }

    public function configure(): void
    {
        Gate::authorize('viewAny', Referee::class);
    }

    /** @return array<int, Column> */
    public function columns(): array
    {
        return [
            Column::make(__('referees.name'), 'full_name')
                ->searchable(function (RefereeBuilder $builder, string $searchTerm): void {
                    $builder->whereNameMatches($searchTerm);
                }),
            Column::make(__('core.status'), 'status')
                ->label(fn (Referee $row) => $row->status->label())
                ->excludeFromColumnSelect(),
            FirstEmploymentDateColumn::make(__('employments.started_at')),
        ];
    }

    /** @return array<int, Filter> */
    public function filters(): array
    {
        return [
            SelectFilter::make(__('core.status'))
                ->setFilterPillTitle(__('core.status'))
                ->options([
                    '' => __('core.all'),
                    'employed' => 'Employed',
                    'future_employment' => 'Awaiting Employment',
                    'released' => 'Released',
                    'unemployed' => 'Unemployed',
                    'retired' => 'Retired',
                ])
                ->filter(function (RefereeBuilder $builder, string $value): void {
                    /** @var RefereeBuilder<Referee> $builder */
                    match ($value) {
                        'employed' => $builder->employed(),
                        'future_employment' => $builder->futureEmployed(),
                        'released' => $builder->released(),
                        'unemployed' => $builder->unemployed(),
                        'retired' => $builder->retired(),
                        default => null,
                    };
                }),
            FirstEmploymentFilter::make('Employment Date')->setFields('employments', 'employments.started_at', 'employments.ended_at'),
        ];
    }

    public function delete(Referee $referee): void
    {
        Gate::authorize('delete', $referee);

        resolve(DeleteAction::class)->handle($referee);
        session()->flash('status', 'Referee successfully deleted.');
    }

    public function clearFromInjury(Referee $referee): void
    {
        $this->handleRefereeAction('heal', $referee->id);
    }

    public function employ(Referee $referee): void
    {
        $this->handleRefereeAction('employ', $referee->id);
    }

    public function injure(Referee $referee): void
    {
        $this->handleRefereeAction('injure', $referee->id);
    }

    public function reinstate(Referee $referee): void
    {
        $this->handleRefereeAction('reinstate', $referee->id);
    }

    public function release(Referee $referee): void
    {
        $this->handleRefereeAction('release', $referee->id);
    }

    public function restore(int $refereeId): void
    {
        $this->handleRefereeAction('restore', $refereeId);
    }

    public function retire(Referee $referee): void
    {
        $this->handleRefereeAction('retire', $referee->id);
    }

    public function suspend(Referee $referee): void
    {
        $this->handleRefereeAction('suspend', $referee->id);
    }

    public function unretire(Referee $referee): void
    {
        $this->handleRefereeAction('unretire', $referee->id);
    }

    public function handleRefereeAction(string $action, int $refereeId): void
    {
        $referee = $action === 'restore'
            ? Referee::onlyTrashed()->findOrFail($refereeId)
            : Referee::query()->findOrFail($refereeId);

        match ($action) {
            'employ' => $this->executeAuthorizedRosterAction('employ', 'employed', RosterEntityType::Referee, $referee, fn () => resolve(EmployAction::class)->handle($referee)),
            'release' => $this->executeAuthorizedRosterAction('release', 'released', RosterEntityType::Referee, $referee, fn () => resolve(ReleaseAction::class)->handle($referee)),
            'retire' => $this->executeAuthorizedRosterAction('retire', 'retired', RosterEntityType::Referee, $referee, fn () => resolve(RetireAction::class)->handle($referee)),
            'unretire' => $this->executeAuthorizedRosterAction('unretire', 'unretired', RosterEntityType::Referee, $referee, fn () => resolve(UnretireAction::class)->handle($referee)),
            'suspend' => $this->executeAuthorizedRosterAction('suspend', 'suspended', RosterEntityType::Referee, $referee, fn () => resolve(SuspendAction::class)->handle($referee)),
            'reinstate' => $this->executeAuthorizedRosterAction('reinstate', 'reinstated', RosterEntityType::Referee, $referee, fn () => resolve(ReinstateAction::class)->handle($referee)),
            'injure' => $this->executeAuthorizedRosterAction('injure', 'injured', RosterEntityType::Referee, $referee, fn () => resolve(InjureAction::class)->handle($referee)),
            'heal' => $this->executeAuthorizedRosterAction('clearFromInjury', 'healed', RosterEntityType::Referee, $referee, fn () => resolve(HealAction::class)->handle($referee)),
            'restore' => $this->executeAuthorizedRosterAction('restore', 'restored', RosterEntityType::Referee, $referee, fn () => resolve(RestoreAction::class)->handle($referee)),
            default => null,
        };
    }
}
