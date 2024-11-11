<?php

declare(strict_types=1);

namespace App\Livewire\Concerns;

trait BaseTableTrait
{
    use HasActionColumn;

    protected array $actionLinksToDisplay = ['view' => true, 'edit' => true, 'delete' => true];

    protected bool $showActionColumn = true;

    public function configuringBaseTableTrait()
    {
        $this->setPrimaryKey('id')
            ->setColumnSelectDisabled()
            ->setSearchPlaceholder('search '.$this->databaseTableName)
            ->setPaginationEnabled()
            ->addAdditionalSelects([$this->databaseTableName.'.id as id'])
            ->setPerPageAccepted([5, 10, 25, 50, 100])
            ->setLoadingPlaceholderContent('Loading')
            ->setLoadingPlaceholderEnabled();
    }

    public function appendColumns(): array
    {
        return $this->showActionColumn ? [
            $this->getDefaultActionColumn(),
        ] : [];
    }
}
