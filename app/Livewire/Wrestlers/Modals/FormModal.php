<?php

declare(strict_types=1);

namespace App\Livewire\Wrestlers\Modals;

use App\Livewire\Base\BaseFormModal;
use App\Livewire\Wrestlers\Forms\CreateEditForm;
use App\Models\Wrestlers\Wrestler;
use Illuminate\Support\Str;
use Illuminate\View\View;

/**
 * @extends BaseFormModal<CreateEditForm, Wrestler>
 */
class FormModal extends BaseFormModal
{
    protected function getFormClass(): string
    {
        return CreateEditForm::class;
    }

    protected function getModelClass(): string
    {
        return Wrestler::class;
    }

    protected function getModalPath(): string
    {
        return 'livewire.wrestlers.modals.form-modal';
    }

    protected function getDummyDataFields(): array
    {
        return [
            'name' => fn () => Str::of(fake()->sentence(2))->title()->value(),
            'hometown' => fn () => fake()->city().', '.fake('en_US')->stateAbbr(),
            'height_feet' => fn () => fake()->numberBetween(5, 7),
            'height_inches' => fn () => fake()->numberBetween(0, 11),
            'weight' => fn () => fake()->numberBetween(180, 350),
            'signature_move' => fn () => Str::of(fake()->optional(0.8)->sentence(3))->title()->value(),
            'employment_date' => fn () => ($date = fake()->optional(0.8)->dateTimeBetween('now', '+3 month')) ? $date->format('Y-m-d H:i:s') : null,
        ];
    }

    public function render(): View
    {
        return view($this->modalFormPath ?? 'livewire.wrestlers.modals.form-modal');
    }
}
