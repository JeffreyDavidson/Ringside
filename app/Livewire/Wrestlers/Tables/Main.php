<?php

declare(strict_types=1);

namespace App\Livewire\Wrestlers\Tables;

use App\Actions\Wrestlers\DeleteAction;
use App\Builders\Roster\IndividualBuilder;
use App\Builders\Roster\WrestlerBuilder;
use App\Enums\Shared\EmploymentStatus;
use App\Livewire\Base\Tables\BaseTable;
use App\Livewire\Components\Tables\Columns\FirstEmploymentDateColumn;
use App\Livewire\Components\Tables\Filters\FirstEmploymentFilter;
use App\Livewire\Table\Column;
use App\Livewire\Table\Filter;
use App\Livewire\Table\Filters\SelectFilter;
use App\Models\Roster\Wrestlers\Wrestler;
use Illuminate\Database\Eloquent\Collection;

/** @extends BaseTable<Wrestler> */
class Main extends BaseTable
{
    #[\Override]
    protected bool $showActionColumn = true;

    #[\Override]
    protected string $modelClass = Wrestler::class;

    #[\Override]
    protected ?string $indexView = 'livewire.wrestlers.tables.main';

    #[\Override]
    protected string $actionsView = 'components.tables.columns.wrestler-actions';

    #[\Override]
    protected string $actionsRowVariable = 'wrestler';

    #[\Override]
    protected string $databaseTableName = 'wrestlers';

    #[\Override]
    protected string $routeBasePath = 'wrestlers';

    #[\Override]
    protected string $resourceName = 'wrestlers';

    /** @return WrestlerBuilder<Wrestler> */
    public function builder(): WrestlerBuilder
    {
        return Wrestler::query()
            ->withFirstEmployment()
            ->oldest('name')
            ->oldest('id');
    }

    #[\Override]
    protected function projectRowState(Collection $rows): void
    {
        $rows->loadExists(IndividualBuilder::ROSTER_STATE);
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
    #[\Override]
    public function filters(): array
    {
        return [
            SelectFilter::make(__('core.status'), 'status')
                ->options(EmploymentStatus::filterOptions())
                ->filter(function (WrestlerBuilder $builder, string $value): void {
                    $status = EmploymentStatus::tryFrom($value);

                    if ($status !== null) {
                        $builder->whereEmploymentStatus($status);
                    }
                }),
            FirstEmploymentFilter::make(__('core.employment_date'), 'employment_date')->setFields('employments', 'employments.started_at', 'employments.ended_at'),
        ];
    }

    public function delete(Wrestler $wrestler, DeleteAction $deleteAction): void
    {
        $this->deleteRecord($wrestler, $deleteAction->handle(...), __('wrestlers.actions.deleted'));
    }
}
