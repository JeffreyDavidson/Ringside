<?php

declare(strict_types=1);

namespace App\Livewire\Referees\Tables;

use App\Actions\Referees\DeleteAction;
use App\Builders\Roster\RefereeBuilder;
use App\Enums\Shared\EmploymentStatus;
use App\Livewire\Base\Tables\BaseTable;
use App\Livewire\Components\Tables\Columns\FirstEmploymentDateColumn;
use App\Livewire\Components\Tables\Filters\FirstEmploymentFilter;
use App\Livewire\Concerns\ExecutesBusinessActions;
use App\Livewire\Table\Column;
use App\Livewire\Table\Filter;
use App\Livewire\Table\Filters\SelectFilter;
use App\Models\Roster\Referees\Referee;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Gate;

/** @extends BaseTable<Referee> */
class Main extends BaseTable
{
    use ExecutesBusinessActions;

    #[\Override]
    protected bool $showActionColumn = true;

    #[\Override]
    protected string $databaseTableName = 'referees';

    #[\Override]
    protected string $routeBasePath = 'referees';

    #[\Override]
    protected string $resourceName = 'referees';

    /** @return RefereeBuilder<Referee> */
    public function builder(): RefereeBuilder
    {
        return Referee::query()
            ->withEmploymentStatusState()
            ->withFirstEmployment()
            ->oldest('last_name');
    }

    protected function configure(): void
    {
        Gate::authorize('viewAny', Referee::class);
    }

    #[\Override]
    public function render(): View
    {
        return view('livewire.referees.tables.main', [
            'rows' => $this->getRows(),
            'perPageOptions' => $this->perPageAccepted,
            'beforeWrapperView' => $this->beforeWrapperView,
        ]);
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

    protected function getDefaultActionColumn(): Column
    {
        return Column::make(__('core.actions'))
            ->label(fn (Referee $row) => view('components.tables.columns.referee-actions', [
                'referee' => $row,
            ])->render())
            ->html()
            ->excludeFromColumnSelect();
    }

    /** @return array<int, Filter> */
    #[\Override]
    public function filters(): array
    {
        return [
            SelectFilter::make(__('core.status'))
                ->options(EmploymentStatus::filterOptions())
                ->filter(function (RefereeBuilder $builder, string $value): void {
                    /** @var RefereeBuilder<Referee> $builder */
                    $status = EmploymentStatus::tryFrom($value);

                    if ($status !== null) {
                        $builder->whereEmploymentStatus($status);
                    }
                }),
            FirstEmploymentFilter::make('Employment Date')->setFields('employments', 'employments.started_at', 'employments.ended_at'),
        ];
    }

    public function delete(Referee $referee, DeleteAction $deleteAction): void
    {
        Gate::authorize('delete', $referee);

        $this->executeBusinessAction(function () use ($deleteAction, $referee): void {
            $deleteAction->handle($referee);
        }, __('referees.actions.deleted'));
    }
}
