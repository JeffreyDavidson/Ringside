<?php

declare(strict_types=1);

namespace App\Livewire\Wrestlers\Forms;

use App\Data\Wrestlers\WrestlerData;
use App\Livewire\Base\BaseForm;
use App\Livewire\Concerns\HasEmploymentDateField;
use App\Models\Roster\Managers\Manager;
use App\Models\Roster\Wrestlers\Wrestler;
use App\Rules\Managers\IsAssignable;
use App\ValueObjects\Height;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;
use Override;

/** @extends BaseForm<Wrestler> */
class CreateEditForm extends BaseForm
{
    use HasEmploymentDateField;

    public string $name = '';

    public string $hometown = '';

    public int $height_feet = 0;

    public int $height_inches = 0;

    public int $weight = 0;

    public ?string $signature_move = '';

    /** @var array<int, int> */
    public array $managers = [];

    public Carbon|string|null $employment_date = '';

    protected function loadModelData(Model $model): void
    {
        $this->employment_date = $this->loadEmploymentDate($model) ?? '';

        $height = $model->height;
        $this->height_feet = (int) floor($height->toInches() / 12);
        $this->height_inches = $height->toInches() % 12;

        $this->managers = $model->currentManagers
            ->map(fn (Manager $manager): int => $manager->id)
            ->all();
    }

    public function toData(): WrestlerData
    {
        return new WrestlerData(
            name: $this->name,
            height: new Height($this->height_feet, $this->height_inches),
            weight: $this->weight,
            hometown: $this->hometown,
            signature_move: $this->signature_move ?: null,
            employment_date: $this->employmentDateInstant($this->employment_date === null ? null : (string) $this->employment_date),
            managers: Manager::query()->whereKey($this->managers)->get(),
        );
    }

    public function wrestler(): Wrestler
    {
        return Wrestler::query()->findOrFail($this->modelId);
    }

    protected function rules(): array
    {
        $wrestler = $this->isEditing() ? $this->wrestler() : null;

        return [
            'name' => ['required', 'string', 'max:255', $this->uniqueInPromotion('wrestlers', 'name')],
            'hometown' => ['required', 'string', 'max:255'],
            'height_feet' => ['required', 'integer', 'min:0', 'max:7'],
            'height_inches' => ['required', 'integer', 'min:0', 'max:11', Rule::when($this->height_feet === 0, ['min:1'])],
            'weight' => ['required', 'integer', 'digits:3'],
            'signature_move' => ['nullable', 'string', 'max:255', $this->uniqueInPromotion('wrestlers', 'signature_move')],
            'employment_date' => $this->employmentDateRules($wrestler),
            'managers' => ['array'],
            'managers.*' => ['bail', 'integer', $this->existsInPromotion('managers'), new IsAssignable($this->currentManagerIds($wrestler))],
        ];
    }

    /**
     * The managers the edited wrestler already has: they stay on it even if they are no longer available.
     *
     * @return array<int, int>
     */
    private function currentManagerIds(?Wrestler $wrestler): array
    {
        return $wrestler?->currentManagers()->get()->map(fn (Manager $manager): int => $manager->id)->all() ?? [];
    }

    /** @return array<string, string> */
    protected function messages(): array
    {
        return [
            'height_feet.min' => __('wrestlers.validation.height_feet'),
            'height_feet.max' => __('wrestlers.validation.height_feet'),
            'height_inches.min' => __('wrestlers.validation.height_minimum'),
            'height_inches.max' => __('wrestlers.validation.height_inches'),
            'weight.digits' => __('wrestlers.validation.weight'),
        ];
    }

    #[Override]
    protected function validationAttributes(): array
    {
        return [
            'height_feet' => __('wrestlers.validation.attributes.height_in_feet'),
            'height_inches' => __('wrestlers.validation.attributes.height_in_inches'),
            'signature_move' => __('wrestlers.validation.attributes.signature_move'),
            'employment_date' => __('wrestlers.validation.attributes.employment_date'),
            'managers' => __('wrestlers.validation.attributes.managers'),
            'managers.*' => __('wrestlers.validation.attributes.manager'),
        ];
    }
}
