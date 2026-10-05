<?php

declare(strict_types=1);

namespace App\Livewire\Referees\Forms;

use App\Data\Referees\RefereeData;
use App\Livewire\Base\BaseForm;
use App\Models\Roster\Referees\Referee;
use App\Rules\Shared\CanChangeEmploymentDate;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Livewire\Attributes\Locked;

/** @extends BaseForm<Referee> */
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

    public function toData(): RefereeData
    {
        return new RefereeData(
            first_name: $this->first_name,
            last_name: $this->last_name,
            employment_date: $this->employment_date ? Carbon::parse($this->employment_date) : null,
        );
    }

    public function referee(): Referee
    {
        return Referee::query()->findOrFail($this->modelId);
    }

    protected function rules(): array
    {
        $referee = $this->isEditing() ? $this->referee() : null;

        return [
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'employment_date' => ['nullable', 'date', new CanChangeEmploymentDate($referee)],
        ];
    }

    #[\Override]
    protected function validationAttributes(): array
    {
        return [
            'first_name' => 'first name',
            'last_name' => 'last name',
            'employment_date' => 'employment date',
        ];
    }
}
