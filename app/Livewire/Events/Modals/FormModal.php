<?php

declare(strict_types=1);

namespace App\Livewire\Events\Modals;

use App\Livewire\Concerns\BaseModal;
use App\Livewire\Events\EventForm;
use App\Models\Event;
use App\Models\Venue;
use App\Traits\Data\PresentsVenuesList;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

class FormModal extends BaseModal
{
    use PresentsVenuesList;

    protected string $modelType = Event::class;

    protected string $modalLanguagePath = 'events';

    protected string $modalFormPath = 'events.modals.form-modal';

    public EventForm $modelForm;

    public function fillDummyFields(): void
    {
        /** @var Carbon|null $datetime */
        $datetime = fake()->optional(0.8)->dateTimeBetween('now', '+3 month');

        /** @var Venue $venue */
        $venue = Venue::query()->inRandomOrder()->first();

        $this->modelForm->name = Str::of(fake()->sentence(2))->title()->value();
        $this->modelForm->date = $datetime?->format('Y-m-d H:i:s');
        $this->modelForm->venue = $venue->id;
        $this->modelForm->preview = Str::of(fake()->text())->value();
    }
}
