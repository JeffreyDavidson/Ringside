<?php

declare(strict_types=1);

namespace App\Livewire\Referees\Forms;

use App\Data\Referees\RefereeData;
use App\Livewire\Base\BasePersonForm;
use App\Models\Roster\Referees\Referee;

/** @extends BasePersonForm<Referee> */
class CreateEditForm extends BasePersonForm
{
    public function toData(): RefereeData
    {
        return new RefereeData(
            first_name: $this->first_name,
            last_name: $this->last_name,
            employment_date: $this->employmentDateInstant($this->employment_date),
        );
    }

    public function referee(): Referee
    {
        return Referee::query()->findOrFail($this->modelId);
    }

    protected function personBeingEdited(): Referee
    {
        return $this->referee();
    }

    protected function translationFile(): string
    {
        return 'referees';
    }
}
