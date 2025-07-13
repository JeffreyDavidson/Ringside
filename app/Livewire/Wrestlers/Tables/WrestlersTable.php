<?php

declare(strict_types=1);

namespace App\Livewire\Wrestlers\Tables;

use App\Actions\Wrestlers\RestoreAction;
use App\Builders\Roster\WrestlerBuilder;
use App\Enums\Shared\EmploymentStatus;
use App\Livewire\Base\Tables\BaseTableWithActions;
use App\Livewire\Components\Tables\Columns\FirstEmploymentDateColumn;
use App\Livewire\Components\Tables\Filters\FirstEmploymentFilter;
use App\Livewire\Wrestlers\Components\WrestlerActionsComponent;
use App\Models\Wrestlers\Wrestler;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Rappasoft\LaravelLivewireTables\Views\Column;
use Rappasoft\LaravelLivewireTables\Views\Filter;
use Rappasoft\LaravelLivewireTables\Views\Filters\SelectFilter;

class WrestlersTable extends BaseTableWithActions
{
    protected string $databaseTableName = 'wrestlers';

    protected string $routeBasePath = 'wrestlers';

    protected string $resourceName = 'wrestlers';

    /** @return WrestlerBuilder<Wrestler> */
    public function builder(): WrestlerBuilder
    {
        return Wrestler::query()
            ->with('currentEmployment');
    }

    public function configure(): void
    {
        Gate::authorize('viewList', Wrestler::class);
    }

    /**
     * @return array<int, Column>
     **/
    public function columns(): array
    {
        return [
            Column::make(__('wrestlers.name'), 'name')
                ->searchable(),
            Column::make(__('core.status'), 'status')
                ->label(fn ($row) => $row->status?->label() ?? 'Unknown')
                ->excludeFromColumnSelect(),
            Column::make(__('wrestlers.height'), 'height'),
            Column::make(__('wrestlers.weight'), 'weight'),
            Column::make(__('wrestlers.hometown'), 'hometown'),
            FirstEmploymentDateColumn::make(__('employments.started_at')),
        ];
    }

    /**
     * @return array<int, Filter>
     **/
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
                ->filter(function ($builder, string $value) {
                    /** @var WrestlerBuilder $builder */
                    match ($value) {
                        'employed' => $builder->employed(),
                        'future_employment' => $builder->where('status', EmploymentStatus::FutureEmployment),
                        'released' => $builder->released(),
                        'unemployed' => $builder->unemployed(),
                        'retired' => $builder->retired(),
                        default => null,
                    };
                }),
            FirstEmploymentFilter::make('Employment Date')->setFields('employments', 'wrestlers_employments.started_at', 'wrestlers_employments.ended_at'),
        ];
    }

    public function delete(Wrestler $wrestler): void
    {
        $this->deleteModel($wrestler);
    }

    /**
     * Restore a deleted wrestler.
     */
    public function restore(int $wrestlerId): RedirectResponse
    {
        $wrestler = Wrestler::onlyTrashed()->findOrFail($wrestlerId);

        Gate::authorize('restore', $wrestler);

        resolve(RestoreAction::class)->handle($wrestler);

        return back();
    }

    /**
     * Override the default action column to use wrestler-specific actions.
     */
    protected $listeners = ['wrestler-action' => 'handleWrestlerAction'];

    protected function getDefaultActionColumn(): Column
    {
        return Column::make(__('core.actions'))
            ->label(fn ($row) => view('components.tables.columns.wrestler-actions', [
                'wrestler' => $row,
            ])->render())
            ->html()
            ->excludeFromColumnSelect();
    }

    public function handleWrestlerAction(string $action, int $wrestlerId): void
    {
        $wrestler = Wrestler::findOrFail($wrestlerId);

        // Delegate to the WrestlerActionsComponent
        $actionsComponent = new WrestlerActionsComponent();
        $actionsComponent->wrestler = $wrestler;

        match ($action) {
            'employ' => $actionsComponent->employ(),
            'release' => $actionsComponent->release(),
            'retire' => $actionsComponent->retire(),
            'unretire' => $actionsComponent->unretire(),
            'suspend' => $actionsComponent->suspend(),
            'reinstate' => $actionsComponent->reinstate(),
            'injure' => $actionsComponent->injure(),
            'heal' => $actionsComponent->healFromInjury(),
            'restore' => $actionsComponent->restore(),
            default => null,
        };
    }
}
