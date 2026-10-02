<?php

declare(strict_types=1);

namespace App\Livewire\Wrestlers\Forms;

use App\Data\Wrestlers\WrestlerData;
use App\Livewire\Base\BaseForm;
use App\Models\Roster\Wrestlers\Wrestler;
use App\Rules\Shared\CanChangeEmploymentDate;
use App\ValueObjects\Height;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;

/** @extends BaseForm<Wrestler> */
class CreateEditForm extends BaseForm
{
    public string $name = '';

    public string $hometown = '';

    public int $height_feet = 0;

    public int $height_inches = 0;

    public int $weight = 0;

    public ?string $signature_move = '';

    public Carbon|string|null $employment_date = '';

    protected function loadModelData(Model $model): void
    {
        $this->employment_date = $model->firstEmployment?->started_at?->toDateString();

        $height = $model->height;
        $this->height_feet = (int) floor($height->toInches() / 12);
        $this->height_inches = $height->toInches() % 12;
    }

    public function toData(): WrestlerData
    {
        return new WrestlerData(
            name: $this->name,
            height: new Height($this->height_feet, $this->height_inches),
            weight: $this->weight,
            hometown: $this->hometown,
            signature_move: $this->signature_move ?: null,
            employment_date: $this->employment_date ? Carbon::parse($this->employment_date) : null,
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
            'employment_date' => ['nullable', 'date', new CanChangeEmploymentDate($wrestler)],
        ];
    }

    #[\Override]
    protected function validationAttributes(): array
    {
        return [
            'height_feet' => 'height in feet',
            'height_inches' => 'height in inches',
            'signature_move' => 'signature move',
            'employment_date' => 'employment date',
        ];
    }
}
