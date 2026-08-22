<?php

declare(strict_types=1);

namespace App\Livewire\Concerns;

use App\Livewire\Concerns\Columns\HasActionColumn;
use App\Livewire\Table\Column;

trait BaseTableTrait
{
    use HasActionColumn;

    protected bool $showActionColumn = false;

    protected string $databaseTableName = '';

    protected string $routeBasePath = '';

    protected string $resourceName = '';

    public function configuringBaseTableTrait(): void
    {
        $this->addAdditionalSelects([$this->databaseTableName.'.id as id'])
            ->setPerPageAccepted([5, 10, 25, 50, 100])
            ->setSearchPlaceholder('Search '.$this->resourceName);

        $this->setConfigurableAreas([
            'before-wrapper' => $this->routeBasePath.'.index.table-pre',
        ]);

    }

    /** @return array<int, Column> */
    protected function additionalColumns(): array
    {
        return $this->showActionColumn ? [
            $this->getDefaultActionColumn(),
        ] : [];
    }
}
