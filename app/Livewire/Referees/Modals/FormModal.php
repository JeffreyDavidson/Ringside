<?php

declare(strict_types=1);

namespace App\Livewire\Referees\Modals;

use App\Livewire\Concerns\BaseModal;
use App\Livewire\Referees\RefereeForm;
use App\Models\Referee;
use Illuminate\Support\Carbon;

class FormModal extends BaseModal
{
    protected string $modelType = Referee::class;

    protected string $modalLanguagePath = 'referees';

    protected string $modalFormPath = 'referees.modals.form-modal';

    protected string $modelTitleField = 'full_name';

    protected RefereeForm $modelForm;

    public function fillDummyFields(): void
    {
        /** @var Carbon|null $datetime */
        $datetime = fake()->optional(0.8)->dateTimeBetween('now', '+3 month');

        $this->modelForm->first_name = fake()->firstName();
        $this->modelForm->last_name = fake()->lastName();
        $this->modelForm->start_date = $datetime?->format('Y-m-d H:i:s');
    }
}
