<?php

namespace Tests\Factories;

use App\Enums\RefereeStatus;
use App\Models\Referee;
use Carbon\Carbon;
use Christophrumpel\LaravelFactoriesReloaded\BaseFactory;
use Faker\Generator as Faker;

class RefereeFactory extends BaseFactory
{
    /** @var $softDeleted */
    public $softDeleted = false;

    protected string $modelClass = Referee::class;

    public function create(array $extra = []): Referee
    {
        $referee = parent::build($extra);

        $referee->save();

        if ($this->softDeleted) {
            $referee->delete();
        }

        return $referee;
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
            'status' => RefereeStatus::__default,
        ];
    }

    public function bookable(): RefereeFactory
    {
        $clone = tap(clone $this)->overwriteDefaults([
            'status' => RefereeStatus::BOOKABLE,
        ]);

        $clone = $clone->withFactory(EmploymentFactory::new()->started(Carbon::yesterday()), 'employments', 1);

        return $clone;
    }

    public function withFutureEmployment(): self
    {
        $clone = tap(clone $this)->overwriteDefaults([
            'status' => RefereeStatus::FUTURE_EMPLOYMENT,
        ]);

        $clone = $clone->withFactory(EmploymentFactory::new()->started(Carbon::tomorrow()), 'employments', 1);

        return $clone;
    }

    public function unemployed(): self
    {
        return tap(clone $this)->overwriteDefaults([
            'status' => RefereeStatus::UNEMPLOYED,
        ]);
    }

    public function retired(): self
    {
        $clone = tap(clone $this)->overwriteDefaults([
            'status' => RefereeStatus::RETIRED,
        ]);

        $now = now();
        $start = $now->copy()->subDays(2);
        $end = $now->copy()->subDays(1);

        $clone = $clone->withFactory(EmploymentFactory::new()->started($start)->ended($end), 'employments', 1);
        $clone = $clone->withFactory(RetirementFactory::new()->started($end), 'retirements', 1);

        return $clone;
    }

    public function released(): self
    {
        $clone = tap(clone $this)->overwriteDefaults([
            'status' => RefereeStatus::RELEASED,
        ]);

        $now = now();
        $start = $now->copy()->subDays(2);
        $end = $now->copy()->subDays(1);

        $clone = $clone->withFactory(EmploymentFactory::new()->started($start)->ended($end), 'employments', 1);

        return $clone;
    }

    public function suspended(): self
    {
        $clone = tap(clone $this)->overwriteDefaults([
            'status' => RefereeStatus::SUSPENDED,
        ]);

        $now = now();
        $start = $now->copy()->subDays(2);
        $end = $now->copy()->subDays(1);

        $clone = $clone->withFactory(EmploymentFactory::new()->started($start), 'employments', 1);
        $clone = $clone->withFactory(SuspensionFactory::new()->started($end), 'suspensions', 1);

        return $clone;
    }

    public function injured(): self
    {
        $clone = tap(clone $this)->overwriteDefaults([
            'status' => RefereeStatus::INJURED,
        ]);

        $now = now();
        $start = $now->copy()->subDays(2);

        $clone = $clone->withFactory(EmploymentFactory::new()->started($start), 'employments', 1);
        $clone = $clone->withFactory(InjuryFactory::new()->started($start->copy()->addDay()), 'injuries', 1);

        return $clone;
    }

    public function softDeleted($delete = true)
    {
        $clone = clone $this;
        $clone->softDeleted = $delete;

        return $clone;
    }
}
