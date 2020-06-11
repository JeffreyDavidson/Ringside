<?php

namespace Tests\Factories;

use App\Enums\RefereeStatus;
use App\Models\Referee;
use Carbon\Carbon;
use Christophrumpel\LaravelFactoriesReloaded\BaseFactory;
use Faker\Generator as Faker;
class RefereeFactory extends BaseFactory
{

    protected string $modelClass = Referee::class;

    public function create(array $extra = []): Referee
    {
        return parent::build($extra);
    }

    public function make(array $extra = []): Referee
    {
        return parent::build($extra, 'make');
    }

    public function getDefaults(Faker $faker): array
    {
        return [
            'first_name' => $faker->firstName,
            'last_name' => $faker->lastName,
            'status' => RefereeStatus::PENDING_EMPLOYMENT,
        ];

    }

    public function bookable(): RefereeFactory
    {
        return tap(clone $this)->overwriteDefaults([
            'status' => RefereeStatus::BOOKABLE,
        ]);
    }

    public function pendingEmployment(): RefereeFactory
    {
        return tap(clone $this)->overwriteDefaults([
            'status' => RefereeStatus::PENDING_EMPLOYMENT,
        ]);
    }

    public function retired(): RefereeFactory
    {
        return tap(clone $this)->overwriteDefaults([
            'status' => RefereeStatus::RETIRED,
        ]);
    }

    public function suspended(): RefereeFactory
    {
        return tap(clone $this)->overwriteDefaults([
            'status' => RefereeStatus::SUSPENDED,
        ]);
    }

    public function injured(): RefereeFactory
    {
        return tap(clone $this)->overwriteDefaults([
            'status' => RefereeStatus::INJURED,
        ]);
    }
}

