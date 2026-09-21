<?php

declare(strict_types=1);

namespace App\Livewire\Concerns;

trait ShowTableTrait
{
    public function mountShowTableTrait(): void
    {
        $this->setSearchPlaceholder('Search '.$this->resourceName)
            ->addAdditionalSelects([$this->databaseTableName.'.id as id'])
            ->setPerPageAccepted([5, 10, 25, 50, 100]);
    }
}
