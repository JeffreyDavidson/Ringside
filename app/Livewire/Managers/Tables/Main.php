<?php

declare(strict_types=1);

namespace App\Livewire\Managers\Tables;

use App\Actions\Managers\DeleteAction;
use App\Builders\Roster\IndividualBuilder;
use App\Builders\Roster\ManagerBuilder;
use App\Enums\Shared\EmploymentStatus;
use App\Livewire\Base\Tables\BaseTable;
use App\Livewire\Components\Tables\Columns\FirstEmploymentDateColumn;
use App\Livewire\Components\Tables\Filters\FirstEmploymentFilter;
use App\Livewire\Concerns\ExecutesBusinessActions;
use App\Livewire\Table\Column;
use App\Livewire\Table\Filter;
use App\Livewire\Table\Filters\SelectFilter;
use App\Models\Roster\Managers\Manager;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Gate;

/** @extends BaseTable<Manager> */
class Main extends BaseTable
{
    use ExecutesBusinessActions;

    #[\Override]
    protected bool $showActionColumn = true;

    #[\Override]
    protected string $databaseTableName = 'managers';

    #[\Override]
    protected string $routeBasePath = 'managers';

    #[\Override]
    protected string $resourceName = 'managers';

    /**
     * @return ManagerBuilder<Manager>
     */
    public function builder(): ManagerBuilder
    {
        return Manager::query()
            ->withFirstEmployment()
            ->oldest('last_name')
            ->orderBy('managers.id');
    }

    #[\Override]
    protected function projectRowState(Collection $rows): void
    {
        $rows->loadExists([...IndividualBuilder::EMPLOYMENT_STATUS_STATE, ...IndividualBuilder::AVAILABILITY_STATE]);
    }

    protected function configure(): void
    {
        Gate::authorize('viewAny', Manager::class);
    }

    #[\Override]
    public function render(): View
    {
        return view('livewire.managers.tables.main', [
            'rows' => $this->getRows(),
            'perPageOptions' => $this->perPageAccepted,
            'beforeWrapperView' => $this->beforeWrapperView,
        ]);
    }

    /**
     * @return array<int, Column>
     */
    public function columns(): array
    {
        return [
            Column::make(__('managers.name'), 'full_name')
                ->searchable(function (ManagerBuilder $builder, string $searchTerm): void {
                    $builder->whereNameMatches($searchTerm);
                }),
            Column::make(__('core.status'), 'status')
                ->label(fn (Manager $row) => $row->status->label())
                ->excludeFromColumnSelect(),
            FirstEmploymentDateColumn::make(__('employments.started_at')),
        ];
    }

    protected function getDefaultActionColumn(): Column
    {
        return Column::make(__('core.actions'))
            ->label(fn (Manager $row) => view('components.tables.columns.manager-actions', [
                'manager' => $row,
            ])->render())
            ->html()
            ->excludeFromColumnSelect();
    }

    /**
     * @return array<int, Filter>
     */
    #[\Override]
    public function filters(): array
    {
        return [
            SelectFilter::make(__('core.status'))
                ->options(EmploymentStatus::filterOptions())
                ->filter(function (ManagerBuilder $builder, string $value): void {
                    /** @var ManagerBuilder<Manager> $builder */
                    $status = EmploymentStatus::tryFrom($value);

                    if ($status !== null) {
                        $builder->whereEmploymentStatus($status);
                    }
                }),
            FirstEmploymentFilter::make('Employment Date')->setFields('employments', 'employments.started_at', 'employments.ended_at'),
        ];
    }

    public function delete(Manager $manager, DeleteAction $deleteAction): void
    {
        Gate::authorize('delete', $manager);

        $this->executeBusinessAction(function () use ($deleteAction, $manager): void {
            $deleteAction->handle($manager);
        }, __('managers.actions.deleted'));

        $this->forgetMetadata();
    }
}
