<?php

declare(strict_types=1);

namespace App\Livewire\Events\Modals;

use App\Livewire\Concerns\BaseModal;
use App\Livewire\Events\EventForm;
use App\Models\Event;
use App\Models\Venue;
use App\Traits\Data\PresentsVenueList;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

class FormModal extends BaseModal
{
    use PresentsVenueList;

    protected string $modelType = Event::class;

    protected string $modalLanguagePath = 'events';

    protected string $modalFormPath = 'events.modals.form-modal';

    public EventForm $modelForm;

    public function fillDummyFields()
    {
        $datetime = fake()->optional(0.8)->dateTimeBetween('now', '+3 month');

        $this->modelForm->name = Str::of(fake()->words(2, true))->title()->value();
        $this->modelForm->date = $datetime ? Carbon::instance($datetime)->toDateString() : null;
        $this->modelForm->venue = Venue::query()->inRandomOrder()->first()->id;
        $this->modelForm->preview = fake()->paragraphs(4, true);
    }
}
