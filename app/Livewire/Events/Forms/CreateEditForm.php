<?php

declare(strict_types=1);

namespace App\Livewire\Events\Forms;

use App\Data\Events\EventData;
use App\Livewire\Base\BaseForm;
use App\Livewire\Concerns\Data\PresentsVenuesList;
use App\Models\Events\Event;
use App\Models\Events\Venue;
use App\Rules\Events\DateCanBeChanged;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;

/** @extends BaseForm<Event> */
class CreateEditForm extends BaseForm
{
    use PresentsVenuesList;

    public string $name = '';

    public ?string $date = '';

    public ?int $venue_id = 0;

    public ?string $preview = '';

    public function loadExtraData(): void
    {
        if (isset($this->formModel->venue_id)) {
            $this->venue_id = $this->formModel->venue_id;
        }
    }

    public function toData(): EventData
    {
        return new EventData(
            name: $this->name,
            date: $this->date ? Carbon::parse($this->date) : null,
            venue: $this->venue_id ? Venue::query()->findOrFail($this->venue_id) : null,
            preview: $this->preview ?: null,
        );
    }

    public function event(): Event
    {
        return Event::query()->findOrFail($this->modelId);
    }

    /** @return array<string, array<int, mixed>> */
    protected function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255', Rule::unique('events', 'name')->ignore($this->modelId)],
            'date' => ['bail', 'nullable', 'date', new DateCanBeChanged($this->isEditing() ? $this->event() : null)],
            'venue_id' => ['nullable', 'integer', Rule::exists('venues', 'id')],
            'preview' => ['nullable', 'string'],
        ];
    }

    /** @return array<string, string> */
    public function validationAttributes(): array
    {
        return [
            'name' => 'event name',
            'date' => 'event date',
            'venue_id' => 'venue',
            'preview' => 'event preview',
        ];
    }
}
