<?php

declare(strict_types=1);

namespace App\Livewire\Concerns;

use App\Livewire\Table\Column;

trait BaseTableTrait
{
    protected bool $showActionColumn = false;

    protected string $databaseTableName = '';

    protected string $routeBasePath = '';

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
    abstract protected function getDefaultActionColumn(): Column;

    /** @return array<int, Column> */
    protected function additionalColumns(): array
    {
        return $this->showActionColumn ? [
            $this->getDefaultActionColumn(),
        ] : [];
    }
}
