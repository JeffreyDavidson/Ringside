<?php

declare(strict_types=1);

namespace App\Livewire\Managers\Modals;

use App\Livewire\Concerns\BaseModal;
use App\Livewire\Managers\ManagerForm;
use App\Models\Manager;
use Illuminate\Support\Carbon;

/**
 * @extends BaseModal<ManagerForm, Manager>
 */
final class FormModal extends BaseModal
{
    protected string $modalLanguagePath = 'managers';

    protected string $modalFormPath = 'managers.modals.form-modal';

    protected string $modelTitleField = 'full_name';

    protected $modelForm;

    protected $modelType;

    public function fillDummyFields(): void
    {
        /** @var Carbon|null $datetime */
        $datetime = fake()->optional(0.8)->dateTimeBetween('now', '+3 month');

        $this->modelForm->first_name = fake()->firstName();
        $this->modelForm->last_name = fake()->lastName();
        $this->modelForm->start_date = $datetime?->format('Y-m-d H:i:s');
    }
}
