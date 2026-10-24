<?php

declare(strict_types=1);

namespace App\Livewire\Managers\Forms;

use App\Data\Managers\ManagerData;
use App\Livewire\Base\BasePersonForm;
use App\Models\Roster\Managers\Manager;

/** @extends BasePersonForm<Manager> */
class CreateEditForm extends BasePersonForm
{
    public function toData(): ManagerData
    {
        return new ManagerData(
            first_name: $this->first_name,
            last_name: $this->last_name,
            employment_date: $this->employmentDateInstant($this->employment_date),
        );
    }

    public function manager(): Manager
    {
        return Manager::query()->findOrFail($this->modelId);
    }

    protected function personBeingEdited(): Manager
    {
        return $this->manager();
    }

    protected function translationFile(): string
    {
        return 'managers';
    }
}
