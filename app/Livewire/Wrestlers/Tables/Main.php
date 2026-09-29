<?php

declare(strict_types=1);

namespace App\Livewire\Wrestlers\Tables;

use App\Actions\Wrestlers\DeleteAction;
use App\Builders\Roster\WrestlerBuilder;
use App\Enums\Shared\EmploymentStatus;
use App\Livewire\Base\Tables\BaseTable;
use App\Livewire\Components\Tables\Columns\FirstEmploymentDateColumn;
use App\Livewire\Components\Tables\Filters\FirstEmploymentFilter;
use App\Livewire\Concerns\ExecutesBusinessActions;
use App\Livewire\Table\Column;
use App\Livewire\Table\Filter;
use App\Livewire\Table\Filters\SelectFilter;
use App\Models\Roster\Wrestlers\Wrestler;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Gate;

/** @extends BaseTable<Wrestler> */
class Main extends BaseTable
{
    use ExecutesBusinessActions;

    #[\Override]
    protected bool $showActionColumn = true;

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
            ->withEmploymentStatusState()
            ->withFirstEmployment();
    }

    protected function configure(): void
    {
        Gate::authorize('viewAny', Wrestler::class);
    }

    #[\Override]
    public function render(): View
    {
        return view('livewire.wrestlers.tables.main', [
            'rows' => $this->getRows(),
            'perPageOptions' => $this->perPageAccepted,
        ]);
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
            SelectFilter::make(__('core.status'))
                ->options(EmploymentStatus::filterOptions())
                ->filter(function (WrestlerBuilder $builder, string $value): void {
                    $status = EmploymentStatus::tryFrom($value);

                    if ($status !== null) {
                        $builder->whereEmploymentStatus($status);
                    }
                }),
            FirstEmploymentFilter::make('Employment Date')->setFields('employments', 'employments.started_at', 'employments.ended_at'),
        ];
    }

    public function delete(Wrestler $wrestler, DeleteAction $deleteAction): void
    {
        Gate::authorize('delete', $wrestler);

        $this->executeBusinessAction(function () use ($deleteAction, $wrestler): void {
            $deleteAction->handle($wrestler);
        }, __('wrestlers.actions.deleted'));
    }

    protected function getDefaultActionColumn(): Column
    {
        return Column::make(__('core.actions'))
            ->label(fn (Wrestler $row) => view('components.tables.columns.wrestler-actions', [
                'wrestler' => $row,
            ])->render())
            ->html()
            ->excludeFromColumnSelect();
    }
}
