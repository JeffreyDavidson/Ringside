<?php

declare(strict_types=1);

namespace App\Livewire\Wrestlers\Tables;

use App\Actions\Wrestlers\DeleteAction;
use App\Actions\Wrestlers\RestoreAction;
use App\Builders\Roster\WrestlerBuilder;
use App\Livewire\Base\Tables\BaseTable;
use App\Livewire\Components\Tables\Columns\FirstEmploymentDateColumn;
use App\Livewire\Components\Tables\Filters\FirstEmploymentFilter;
use App\Livewire\Table\Column;
use App\Livewire\Table\Filter;
use App\Livewire\Table\Filters\SelectFilter;
use App\Livewire\Wrestlers\Components\Actions;
use App\Models\Wrestlers\Wrestler;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;

/** @extends BaseTable<Wrestler> */
class Main extends BaseTable
{
    protected bool $showActionColumn = true;

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
                ->label(fn (Wrestler $row) => $row->status->label())
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
                ->filter(function (WrestlerBuilder $builder, string $value) {
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

    public function delete(Wrestler $wrestler): void
    {
        Gate::authorize('delete', $wrestler);

        resolve(DeleteAction::class)->handle($wrestler);
        session()->flash('status', 'Wrestler successfully deleted.');
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
     *
     * @var array<string, string>
     */
    protected $listeners = ['wrestler-action' => 'handleWrestlerAction'];

    protected function getDefaultActionColumn(): Column
    {
        return Column::make(__('core.actions'))
            ->label(fn (Wrestler $row) => view('components.tables.columns.wrestler-actions', [
                'wrestler' => $row,
            ])->render())
            ->html()
            ->excludeFromColumnSelect();
    }

    public function handleWrestlerAction(string $action, int $wrestlerId): void
    {
        $wrestler = Wrestler::findOrFail($wrestlerId);

        // Delegate to the Actions component
        $actionsComponent = new Actions();
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
