<?php

declare(strict_types=1);

namespace App\Livewire\Events\Forms;

use App\Data\Events\EventData;
use App\Livewire\Base\BaseForm;
use App\Livewire\Concerns\Data\PresentsVenuesList;
use App\Models\Events\Event;
use App\Models\Events\Venue;
use App\Models\Promotions\Promotion;
use App\Rules\Events\DateCanBeChanged;
use App\Services\Promotions\PromotionContextService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\Rule;

/** @extends BaseForm<Event> */
class CreateEditForm extends BaseForm
{
    use PresentsVenuesList;

    public string $name = '';

    public ?string $date = '';

    public ?int $venue_id = 0;

    public ?string $preview = '';

    protected function loadModelData(Model $model): void
    {
        $this->date = $model->local_date?->format('Y-m-d\\TH:i');
        $this->venue_id = $model->venue_id;
    }

    public function toData(): EventData
    {
        return new EventData(
            name: $this->name,
            date: $this->date ? Promotion::parseLocalTime($this->promotion(), $this->date) : null,
            venue: $this->venue_id ? Venue::query()->findOrFail($this->venue_id) : null,
            preview: $this->preview ?: null,
        );
    }

    /** The promotion whose time zone the entered date is read in: the event's own, or the current one for a new event. */
    private function promotion(): ?Promotion
    {
        if ($this->isEditing()) {
            return $this->event()->promotion;
        }

        $context = app(PromotionContextService::class);

        return $context->isEnforced() ? $context->current() : null;
    }

    public function event(): Event
    {
        return Event::query()->findOrFail($this->modelId);
    }

    /** @return array<string, array<int, mixed>> */
    protected function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255', $this->uniqueInPromotion('events', 'name')],
            'date' => ['bail', 'nullable', 'date', new DateCanBeChanged($this->isEditing() ? $this->event() : null)],
            'venue_id' => ['nullable', 'integer', Rule::exists('venues', 'id')],
            'preview' => ['nullable', 'string'],
        ];
    }

    /** @return array<string, string> */
    #[\Override]
    protected function validationAttributes(): array
    {
        return [
            'name' => 'event name',
            'date' => 'event date',
            'venue_id' => 'venue',
            'preview' => 'event preview',
        ];
    }
}
