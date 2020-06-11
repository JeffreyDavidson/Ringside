<?php

namespace Tests\Factories;

use App\Enums\EventStatus;
use App\Models\Event;
use Carbon\Carbon;
use Christophrumpel\LaravelFactoriesReloaded\BaseFactory;
use Faker\Generator as Faker;
use Tests\Factories\VenueFactory;

class EventFactory extends BaseFactory
{
    protected string $modelClass = Event::class;

    public function create(array $extra = []): Event
    {
        return parent::build($extra);
    }

    public function make(array $extra = []): Event
    {
        return parent::build($extra, 'make');
    }

    public function getDefaults(Faker $faker): array
    {
        return [
            'name' => $faker->words(2, true),
            'status' => EventStatus::__default,
            'date' => $faker->dateTime(),
            'venue_id' => VenueFactory::new()->create()->id,
            'preview' => $faker->paragraph(),
        ];

    }

    public function scheduled(): EventFactory
    {
        return tap(clone $this)->overwriteDefaults([
            'status' => EventStatus::SCHEDULED,
            'date' => Carbon::tomorrow()->toDateTimeString(),
        ]);
    }

    public function past(): EventFactory
    {
        return tap(clone $this)->overwriteDefaults([
            'status' => EventStatus::PAST,
            'date' => Carbon::yesterday()->toDateTimeString(),
        ]);
    }

    public function atVenue($venue)
    {
        return tap(clone $this)->overwriteDefaults([
            'venue_id' => $venue->id,
        ]);
    }
}

