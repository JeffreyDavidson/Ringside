<?php

declare(strict_types=1);

namespace App\Livewire\Events;

use App\Livewire\Base\LivewireBaseForm;
use App\Models\Event;
use Illuminate\Support\Carbon;
use Livewire\Attributes\Validate;

class EventForm extends LivewireBaseForm
{
    protected string $formModelType = Event::class;

    public ?Event $formModel;

    #[Validate('required|string|min:5|max:255', as: 'events.name')]
    public string $name = '';

    #[Validate('required|date', as: 'events.date')]
    public Carbon|string $date = '';

    #[Validate('required|integer|exists:venue,id', as: 'events.venue')]
    public int $venue;

    #[Validate('required|string', as: 'events.preview')]
    public string $preview;

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
