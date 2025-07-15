<?php

declare(strict_types=1);

namespace App\Livewire\Venues\Modals;

use App\Livewire\Base\BaseFormModal;
use App\Livewire\Venues\Forms\Form;
use App\Models\Events\Venue;
use Illuminate\Support\Str;

/**
 * @extends BaseFormModal<Form, Venue>
 */
class FormModal extends BaseFormModal
{
    public Form $form;

    protected function getFormClass(): string
    {
        return Form::class;
    }

    protected function getModelClass(): string
    {
        return Venue::class;
    }

    protected function getModalPath(): string
    {
        return 'livewire.venues.modals.form-modal';
    }

    protected function getDummyDataFields(): array
    {
        /**
         * @var string $state
         *
         * @phpstan-ignore-next-line
         */
        $state = fake('en_US')->state();

        return [
            'name' => fn() => Str::of(fake()->sentence(2))->title()->append(' Arena')->value(),
            'street_address' => fn() => fake()->streetAddress(),
            'city' => fn() => fake()->city(),
            'state' => fn() => $state,
            'zipcode' => fn() => (int) Str::of(fake()->postcode())->limit(5)->value(),
        ];
    }

    public function render(): \Illuminate\View\View
    {
        return view($this->modalFormPath ?? 'livewire.venues.modals.form-modal');
    }
}
