<?php

declare(strict_types=1);

namespace App\Livewire\Base;

use App\Livewire\Concerns\HasEmploymentDateField;
use App\Models\Roster\Managers\Manager;
use App\Models\Roster\Referees\Referee;
use Illuminate\Database\Eloquent\Model;
use Override;

/**
 * The create and edit form of a roster member with a first and last name and an optional employment date
 * (managers and referees).
 *
 * @template TModel of Manager|Referee
 *
 * @extends BaseForm<TModel>
 */
abstract class BasePersonForm extends BaseForm
{
    use HasEmploymentDateField;

    public string $first_name = '';

    public string $last_name = '';

    public ?string $employment_date = null;

    /** The translation file that names the form's fields (for example "managers"). */
    abstract protected function translationFile(): string;

    /** @return TModel */
    abstract protected function personBeingEdited(): Manager|Referee;

    protected function loadModelData(Model $model): void
    {
        $this->employment_date = $this->loadEmploymentDate($model);
    }

    protected function rules(): array
    {
        return [
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'employment_date' => $this->employmentDateRules($this->isEditing() ? $this->personBeingEdited() : null),
        ];
    }

    #[Override]
    protected function validationAttributes(): array
    {
        return [
            'first_name' => __("{$this->translationFile()}.validation.attributes.first_name"),
            'last_name' => __("{$this->translationFile()}.validation.attributes.last_name"),
            'employment_date' => __("{$this->translationFile()}.validation.attributes.employment_date"),
        ];
    }
}
