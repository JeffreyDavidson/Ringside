<?php

namespace Tests\Factories;

use App\Enums\WrestlerStatus;
use App\Models\Wrestler;
use Christophrumpel\LaravelFactoriesReloaded\BaseFactory;
use Faker\Generator as Faker;
use Illuminate\Support\Str;

class WrestlerFactory extends BaseFactory
{
    protected string $modelClass = Wrestler::class;

    public function create(array $extra = []): Wrestler
    {
        return parent::build($extra);
    }

    public function make(array $extra = []): Wrestler
    {
        return parent::build($extra, 'make');
    }

    public function getDefaults(Faker $faker): array
    {
        return [
            'name' => $faker->name,
            'height' => $faker->numberBetween(60, 95),
            'weight' => $faker->numberBetween(180, 500),
            'hometown' => $faker->city.', '.$faker->state,
            'signature_move' => Str::title($faker->words(3, true)),
            'status' => WrestlerStatus::__default,
        ];
    }

    public function bookable(): WrestlerFactory
    {
        return tap(clone $this)->overwriteDefaults([
            'status' => WrestlerStatus::BOOKABLE,
        ]);
    }

    public function pendingEmployment(): WrestlerFactory
    {
        return tap(clone $this)->overwriteDefaults([
            'status' => WrestlerStatus::PENDING_EMPLOYMENT,
        ]);
    }

    public function unemployed(): WrestlerFactory
    {
        return tap(clone $this)->overwriteDefaults([
            'status' => WrestlerStatus::UNEMPLOYED,
        ]);
    }

    public function retired(): WrestlerFactory
    {
        return tap(clone $this)->overwriteDefaults([
            'status' => WrestlerStatus::RETIRED,
        ]);
    }

    public function released(): WrestlerFactory
    {
        return tap(clone $this)->overwriteDefaults([
            'status' => WrestlerStatus::RELEASED,
        ]);
    }

    public function suspended(): WrestlerFactory
    {
        return tap(clone $this)->overwriteDefaults([
            'status' => WrestlerStatus::SUSPENDED,
        ]);
    }

    public function injured(): WrestlerFactory
    {
        return tap(clone $this)->overwriteDefaults([
            'status' => WrestlerStatus::INJURED,
        ]);
    }
}
