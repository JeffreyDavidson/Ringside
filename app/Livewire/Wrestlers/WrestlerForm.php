<?php

declare(strict_types=1);

namespace App\Livewire\Wrestlers;

use App\Livewire\Base\LivewireBaseForm;
use App\Models\Wrestler;
use App\Rules\EmploymentStartDateCanBeChanged;
use App\ValueObjects\Height;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Unique;

class WrestlerForm extends LivewireBaseForm
{
    protected string $formModelType = Wrestler::class;

    public ?Wrestler $formModel;

    public string $name = '';

    public string $hometown = '';

    public int $height_feet;

    public int $height_inches;

    public int $weight;

    public ?string $signature_move = '';

    public Carbon|string|null $start_date = '';

    /**
     * @return array<string, list<EmploymentStartDateCanBeChanged|Unique|string>>
     */
    protected function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255', Rule::unique('wrestlers', 'name')->ignore($this->formModel ?? '')],
            'hometown' => ['required', 'string', 'max:255'],
            'height_feet' => ['required', 'integer', 'max:7'],
            'height_inches' => ['required', 'integer', 'max:11'],
            'weight' => ['required', 'integer', 'digits:3'],
            'signature_move' => ['nullable', 'string', 'max:255', Rule::unique('wrestlers', 'signature_move')->ignore($this->formModel ?? '')],
            'start_date' => ['nullable', 'date', new EmploymentStartDateCanBeChanged($this->formModel ?? '')],
        ];
    }

    /**
     * @return array<string, string>
     */
    protected function validationAttributes(): array
    {
        return [
            'height_feet' => 'feet',
            'height_inches' => 'inches',
            'signature_move' => 'signature move',
            'start_date' => 'start date',
        ];
    }

    public function loadExtraData(): void
    {
        $this->start_date = $this->formModel?->firstEmployment?->started_at->toDateString();

        /** @var Height $height */
        $height = $this->formModel?->height;

        $feet = (int) floor($height->toInches() / 12);
        $inches = $height->toInches() % 12;

        $this->height_feet = $feet;
        $this->height_inches = $inches;
    }

    public function store(): bool
    {
        $this->validate();

        $height = new Height($this->height_feet, $this->height_inches);

        if (! isset($this->formModel)) {
            $this->formModel = new Wrestler([
                'name' => $this->name,
                'hometown' => $this->hometown,
                'height' => $height->toInches(),
                'weight' => $this->weight,
                'signature_move' => $this->signature_move,
            ]);
            $this->formModel->save();

            if ($this->start_date) {
                $this->formModel->employments()->create(['started_at' => $this->start_date]);
            }
        } else {
            $this->formModel->update([
                'name' => $this->name,
                'hometown' => $this->hometown,
                'height' => $height->toInches(),
                'weight' => $this->weight,
                'signature_move' => $this->signature_move,
            ]);
        }

        return true;
    }
}
