<?php

declare(strict_types=1);

namespace App\Livewire\Events;

use App\Livewire\Base\LivewireBaseForm;
use App\Models\Event;
use App\Rules\EventDateCanBeChanged;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;

class EventForm extends LivewireBaseForm
{
    protected string $formModelType = Event::class;

    public ?Event $formModel;

    public string $name = '';

    public Carbon|string|null $date = '';

    public int $venue;

    public string $preview;

    protected function rules()
    {
        return [
            'name' => ['required', 'string', 'max:255', Rule::unique('events', 'name')->ignore($this->formModel ?? '')],
            'date' => ['nullable', 'date', new EventDateCanBeChanged($this->formModal ?? new $this->formModelType)],
            'venue' => ['required_with:date', 'integer', Rule::exists('venues', 'id')],
            'preview' => ['required', 'string'],
        ];
    }

    protected function validationAttributes()
    {
        return [
            'height_feet' => 'feet',
            'height_inches' => 'inches',
            'signature_move' => 'signature move',
            'start_date' => 'start date',
        ];
    }

    public function store(): bool
    {
        $this->validate();

        if (! isset($this->formModel)) {
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
}
