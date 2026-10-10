<?php

declare(strict_types=1);

namespace App\Livewire\Referees\Tables;

use App\Actions\Referees\DeleteAction;
use App\Actions\Referees\RestoreAction;
use App\Builders\Roster\IndividualBuilder;
use App\Builders\Roster\RefereeBuilder;
use App\Enums\Roster\RosterEntityType;
use App\Enums\Shared\EmploymentStatus;
use App\Livewire\Base\Tables\BaseTable;
use App\Livewire\Components\Tables\Columns\FirstEmploymentDateColumn;
use App\Livewire\Components\Tables\Filters\FirstEmploymentFilter;
use App\Livewire\Table\Column;
use App\Livewire\Table\Filter;
use App\Livewire\Table\Filters\SelectFilter;
use App\Models\Roster\Referees\Referee;
use Illuminate\Database\Eloquent\Collection;

/** @extends BaseTable<Referee> */
class Main extends BaseTable
{
    #[\Override]
    protected bool $showActionColumn = true;

    #[\Override]
    protected string $modelClass = Referee::class;

    #[\Override]
    protected ?string $indexView = 'livewire.referees.tables.main';

    #[\Override]
    protected string $actionsView = 'components.tables.columns.referee-actions';

    #[\Override]
    protected string $actionsRowVariable = 'referee';

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
            ->withFirstEmployment()
            ->oldest('last_name')
            ->orderBy('referees.id');
    }

    #[\Override]
    protected function projectRowState(Collection $rows): void
    {
        $rows->loadExists(IndividualBuilder::ROSTER_STATE);
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
    #[\Override]
    public function filters(): array
    {
        return [
            SelectFilter::make(__('core.status'), 'status')
                ->options($this->statusOptionsWithDeleted(EmploymentStatus::filterOptions()))
                ->filter(function (RefereeBuilder $builder, string $value): void {
                    /** @var RefereeBuilder<Referee> $builder */
                    if ($this->isDeletedFilterValue($value)) {
                        $builder->onlyTrashed();

                        return;
                    }

                    $status = EmploymentStatus::tryFrom($value);

                    if ($status !== null) {
                        $builder->whereEmploymentStatus($status);
                    }
                }),
            FirstEmploymentFilter::make(__('core.employment_date'), 'employment_date')->setFields('employments', 'employments.started_at', 'employments.ended_at'),
        ];
    }

    public function delete(Referee $referee, DeleteAction $deleteAction): void
    {
        $this->deleteRecord($referee, $deleteAction->handle(...), __('referees.actions.deleted'));
    }

    public function restore(int $refereeId, RestoreAction $restoreAction): void
    {
        $this->restoreRecord(Referee::withTrashed()->findOrFail($refereeId), $restoreAction->handle(...), __('referees.actions.restored'), RosterEntityType::Referee);
    }
}
