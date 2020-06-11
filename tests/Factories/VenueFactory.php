<?php

namespace Tests\Factories;

use App\Models\Venue;
use Christophrumpel\LaravelFactoriesReloaded\BaseFactory;
use Faker\Generator as Faker;
use Illuminate\Support\Str;

class VenueFactory extends BaseFactory
{
    protected string $modelClass = Venue::class;

    public function create(array $extra = []): Venue
    {
        return parent::build($extra);
    }

    public function make(array $extra = []): Venue
    {
        return parent::build($extra, 'make');
    }

    public function getDefaults(Faker $faker): array
    {
        return [
            'name' => $faker->sentence,
            'address1' => $faker->buildingNumber. ' '. $faker->streetName,
            'address2' => $faker->optional()->secondaryAddress,
            'city' => $faker->city,
            'state' => $faker->state,
            'zip' => Str::substr($faker->postcode, 0, 5),
        ];
    }
}

