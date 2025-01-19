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

    public RefereeForm $modelForm;

    public function fillDummyFields()
    {
        $datetime = fake()->optional(0.8)->dateTimeBetween('now', '+3 month');

        $this->modelForm->first_name = fake()->firstName();
        $this->modelForm->last_name = fake()->lastName();
        $this->modelForm->start_date = $datetime ? Carbon::instance($datetime)->toDateString() : null;
    }
}
