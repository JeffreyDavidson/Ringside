<?php

declare(strict_types=1);

namespace App\Livewire\Managers\Forms;

use App\Data\Managers\ManagerData;
use App\Livewire\Base\BaseForm;
use App\Models\Roster\Managers\Manager;
use App\Rules\Shared\CanChangeEmploymentDate;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Livewire\Attributes\Locked;

/** @extends BaseForm<Manager> */
class CreateEditForm extends BaseForm
{
    public string $first_name = '';

    public string $last_name = '';

    public ?string $employment_date = null;

    /** Set when editing someone who has been employed before: their history is kept, so the date field is not offered. */
    #[Locked]
    public bool $hasEmploymentHistory = false;

    protected function loadModelData(Model $model): void
    {
        $this->hasEmploymentHistory = $model->employments()->exists();
        $this->employment_date = null;
    }

    public function toData(): ManagerData
    {
        return new ManagerData(
            first_name: $this->first_name,
            last_name: $this->last_name,
            employment_date: $this->employment_date ? Carbon::parse($this->employment_date) : null,
        );
    }

    public function manager(): Manager
    {
        return Manager::query()->findOrFail($this->modelId);
    }

    protected function rules(): array
    {
        $manager = $this->isEditing() ? $this->manager() : null;

        return [
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'employment_date' => ['nullable', 'date', new CanChangeEmploymentDate($manager)],
        ];
    }

    #[\Override]
    protected function validationAttributes(): array
    {
        return [
            'first_name' => __('managers.validation.attributes.first_name'),
            'last_name' => __('managers.validation.attributes.last_name'),
            'employment_date' => __('managers.validation.attributes.employment_date'),
        ];
    }
}
