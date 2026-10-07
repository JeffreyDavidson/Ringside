<?php

declare(strict_types=1);

namespace App\Livewire\Concerns;

use App\Livewire\Table\Column;

trait BaseTableTrait
{
    protected bool $showActionColumn = false;

    protected string $databaseTableName = '';

    protected string $routeBasePath = '';

    /**
     * Blade view of the row actions menu, rendered with the row under the $actionsRowVariable name.
     *
     * @var view-string
     */
    protected string $actionsView;

    protected string $actionsRowVariable;

    public function mountBaseTableTrait(): void
    {
        $this->addAdditionalSelects([$this->databaseTableName.'.id as id'])
            ->setPerPageAccepted([5, 10, 25, 50, 100])
            ->setSearchPlaceholder('Search '.$this->resourceName);

        $this->setConfigurableAreas([
            'before-wrapper' => $this->routeBasePath.'.index.table-pre',
        ]);
    }

    /**
     * Build the row actions column shown when the table enables it.
     */
    protected function getDefaultActionColumn(): Column
    {
        return Column::make(__('core.actions'))
            ->label(fn (mixed $row) => view($this->actionsView, [
                $this->actionsRowVariable => $row,
            ])->render())
            ->html()
            ->excludeFromColumnSelect();
    }

    /** @return array<int, Column> */
    protected function additionalColumns(): array
    {
        return $this->showActionColumn ? [
            $this->getDefaultActionColumn(),
        ] : [];
    }
}
