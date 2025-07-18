<?php

declare(strict_types=1);

namespace App\Livewire\Wrestlers\Modals;

use App\Livewire\Base\BaseFormModal;
use App\Livewire\Wrestlers\Forms\CreateEditForm;
use App\Models\Wrestlers\Wrestler;
use Illuminate\Support\Str;

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
            'hometown' => fn () => fake()->city().', '.fake('en_US')->state(),
            'height_feet' => fn () => fake()->numberBetween(5, 7),
            'height_inches' => fn () => fake()->numberBetween(0, 11),
            'weight' => fn () => fake()->numberBetween(180, 400),
            'signature_move' => fn () => Str::of(fake()->optional(0.8)->sentence(3))->title()->value(),
            'start_date' => fn () => fake()->optional(0.8)->dateTimeBetween('now', '+3 month')?->format('Y-m-d H:i:s'),
        ];
    }

    public function render(): \Illuminate\View\View
    {
        return view($this->modalFormPath ?? 'livewire.wrestlers.modals.form-modal');
    }
}
