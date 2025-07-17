<?php

declare(strict_types=1);

namespace App\Livewire\Referees\Modals;

use App\Livewire\Concerns\BaseModal;
use App\Livewire\Referees\Forms\Form;
use App\Models\Referees\Referee;
use Illuminate\Support\Carbon;

/**
 * @extends BaseModal<Form, Referee>
 */
class FormModal extends BaseModal
{
    public Form $form;

    protected function getFormClass(): string
    {
        return Form::class;
    }

    protected function getModelClass(): string
    {
        return Referee::class;
    }

    protected function getModalPath(): string
    {
        return 'livewire.referees.modals.form-modal';
    }

    public function fillDummyFields(): void
    {
        /** @var Carbon|null $datetime */
        $datetime = fake()->optional(0.8)->dateTimeBetween('now', '+3 month');

        $this->modelForm->first_name = fake()->firstName();
        $this->modelForm->last_name = fake()->lastName();
        $this->modelForm->employment_date = $datetime?->format('Y-m-d H:i:s');
    }
}
