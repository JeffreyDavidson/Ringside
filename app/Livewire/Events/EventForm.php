<?php

declare(strict_types=1);

namespace App\Livewire\Events;

use App\Livewire\Base\LivewireBaseForm;
use App\Models\Event;
use App\Rules\EventDateCanBeChanged;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Exists;
use Illuminate\Validation\Rules\Unique;
use Livewire\Attributes\Validate;

/**
 * @extends LivewireBaseForm<EventForm, ?Event>
 */
final class EventForm extends LivewireBaseForm
{
    public $formModel;

    #[Validate]
    public string $name = '';

    #[Validate]
    public Carbon|string|null $date = '';

    #[Validate]
    public int $venue;

    #[Validate]
    public string $preview;

    public function store(): bool
    {
        $this->validate();

        if ($this->formModel === null) {
            $this->formModel = new Event([
                'name' => $this->name,
                'date' => $this->date,
                'venue_id' => $this->venue,
                'preview' => $this->preview,
            ]);
            $this->formModel->save();
        } else {
            $this->formModel->update([
                'name' => $this->name,
                'date' => $this->date,
                'venue_id' => $this->venue,
                'preview' => $this->preview,
            ]);
        }

        return true;
    }

    /**
     * @return array<string, list<Unique|Exists|EventDateCanBeChanged|string>>
     */
    protected function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255', Rule::unique('events', 'name')->ignore($this->formModel)],
            'date' => ['nullable', 'date', new EventDateCanBeChanged($this->formModel)],
            'venue' => ['required_with:date', 'integer', Rule::exists('venues', 'id')],
            'preview' => ['required', 'string'],
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
}
