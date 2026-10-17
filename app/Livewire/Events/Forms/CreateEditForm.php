<?php

declare(strict_types=1);

namespace App\Livewire\Events\Forms;

use App\Data\Events\EventData;
use App\Livewire\Base\BaseForm;
use App\Models\Events\Event;
use App\Models\Events\Venue;
use App\Models\Promotions\Promotion;
use App\Rules\Events\DateCanBeChanged;
use App\Rules\Events\LocalTimeExists;
use App\Services\Promotions\PromotionContextService;
use App\Services\Promotions\PromotionTimeZoneService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Exists;

/** @extends BaseForm<Event> */
class CreateEditForm extends BaseForm
{
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
            date: $this->date ? $this->parseDate($this->date) : null,
            venue: $this->venue_id ? Venue::query()->withTrashed()->findOrFail($this->venue_id) : null,
            preview: $this->preview ?: null,
        );
    }

    /** An edited event keeps its stored instant when the date is the one the form was prefilled with (see Event::parseLocalDate()). */
    private function parseDate(string $value): Carbon
    {
        if ($this->isEditing()) {
            return PromotionTimeZoneService::parseEventLocalDate($this->event(), $value);
        }

        return PromotionTimeZoneService::parseLocalTime($this->promotion(), $value);
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

    /** The venue the edited event is stored at, which may since have been deleted; null for a new event. */
    public function storedVenueId(): ?int
    {
        return $this->isEditing() ? $this->event()->venue_id : null;
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
            'date' => ['bail', 'nullable', 'date', new LocalTimeExists($this->promotion()), new DateCanBeChanged($this->isEditing() ? $this->event() : null)],
            'venue_id' => ['nullable', 'integer', $this->venueRule()],
            'preview' => ['nullable', 'string'],
        ];
    }

    /** A deleted venue cannot be newly chosen, but the event's own deleted venue stays valid so its history can be edited. */
    private function venueRule(): Exists
    {
        $storedVenueId = $this->storedVenueId();

        return Rule::exists('venues', 'id')->where(
            fn (Builder $query): Builder => $query->whereNull('deleted_at')->when(
                $storedVenueId !== null,
                fn (Builder $query): Builder => $query->orWhere('id', $storedVenueId),
            ),
        );
    }

    /** @return array<string, string> */
    #[\Override]
    protected function validationAttributes(): array
    {
        return [
            'name' => __('events.validation.attributes.event_name'),
            'date' => __('events.validation.attributes.event_date'),
            'venue_id' => __('events.validation.attributes.venue'),
            'preview' => __('events.validation.attributes.event_preview'),
        ];
    }
}
