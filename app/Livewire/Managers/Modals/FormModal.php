<?php

declare(strict_types=1);

namespace App\Livewire\Managers\Modals;

use App\Livewire\Concerns\BaseModal;
use App\Livewire\Managers\ManagerForm;
use App\Models\Manager;
use Illuminate\Support\Carbon;

class FormModal extends BaseModal
{
    protected string $modelType = Manager::class;

    protected string $modalLanguagePath = 'managers';

    protected string $modalFormPath = 'managers.modals.form-modal';

    protected string $modelTitleField = 'full_name';

    public ManagerForm $modelForm;

    public function fillDummyFields()
    {
        $datetime = fake()->optional(0.8)->dateTimeBetween('now', '+3 month');

        $this->modelForm->first_name = fake()->firstName();
        $this->modelForm->last_name = fake()->lastName();
        $this->modelForm->start_date = $datetime ? Carbon::instance($datetime)->toDateString() : null;
    }
}
