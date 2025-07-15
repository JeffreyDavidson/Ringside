<?php

declare(strict_types=1);

namespace App\Livewire\Events\Modals;

use App\Livewire\Base\BaseFormModal;
use App\Livewire\Events\Forms\Form;
use App\Models\Events\Event;
use App\Models\Events\Venue;
use App\Livewire\Concerns\Data\PresentsVenuesList;
use Illuminate\Support\Str;

/**
 * @extends BaseFormModal<Form, Event>
 */
class FormModal extends BaseFormModal
{
    use PresentsVenuesList;

    public Form $form;

    protected function getFormClass(): string
    {
        return Form::class;
    }

    protected function getModelClass(): string
    {
        return Event::class;
    }

    protected function getModalPath(): string
    {
        return 'livewire.events.modals.form-modal';
    }

    protected function getDummyDataFields(): array
    {
        /** @var Venue $venue */
        $venue = Venue::query()->inRandomOrder()->first();

        return [
            'name' => fn() => Str::of(fake()->sentence(2))->title()->value(),
            'date' => fn() => fake()->optional(0.8)->dateTimeBetween('now', '+3 month')?->format('Y-m-d H:i:s'),
            'venue' => fn() => $venue->id,
            'preview' => fn() => Str::of(fake()->text())->value(),
        ];
    }

    public function render(): \Illuminate\View\View
    {
        return view($this->modalFormPath ?? 'livewire.events.modals.form-modal');
    }
}
