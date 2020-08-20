<?php

namespace Tests\Factories;

use App\Enums\RefereeStatus;
use App\Models\Referee;
use Christophrumpel\LaravelFactoriesReloaded\BaseFactory;
use Faker\Generator as Faker;

class RefereeFactory extends BaseFactory
{
    /** @var EmploymentFactory|null */
    public $employmentFactory;

    /** @var RetirementFactory|null */
    public $retirementFactory;

    /** @var SuspensionFactory|null */
    public $suspensionFactory;

    /** @var InjuryFactory|null */
    public $injuryFactory;

    /** @var $softDeleted */
    public $softDeleted = false;

    protected string $modelClass = Referee::class;

    public function create(array $extra = []): Referee
    {
        $referee = parent::build($extra);

        if ($this->employmentFactory) {
            $this->employmentFactory->forReferee($referee)->create();
        }

        if ($this->suspensionFactory) {
            $this->suspensionFactory->forReferee($referee)->create();
        }

        if ($this->retirementFactory) {
            $this->retirementFactory->forReferee($referee)->create();
        }

        if ($this->injuryFactory) {
            $this->injuryFactory->forReferee($referee)->create();
        }

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

    public function employ(EmploymentFactory $employmentFactory = null)
    {
        $clone = clone $this;

        $clone->employmentFactory = $employmentFactory ?? EmploymentFactory::new()->started();

        return $clone;
    }

    public function bookable(EmploymentFactory $employmentFactory = null): RefereeFactory
    {
        $clone = tap(clone $this)->overwriteDefaults([
            'status' => RefereeStatus::BOOKABLE,
        ]);

        $clone->employmentFactory = $employmentFactory ?? EmploymentFactory::new()->started(now());

        return $clone;
    }

    public function withFutureEmployment(): RefereeFactory
    {
        $clone = tap(clone $this)->overwriteDefaults([
            'status' => RefereeStatus::FUTURE_EMPLOYMENT,
        ]);

        $clone->employmentFactory = $employmentFactory ?? EmploymentFactory::new()->started(now()->addDay());

        return $clone;
    }

    public function unemployed(): RefereeFactory
    {
        return tap(clone $this)->overwriteDefaults([
            'status' => RefereeStatus::UNEMPLOYED,
        ]);
    }

    public function retired(EmploymentFactory $employmentFactory = null, RetirementFactory $retirementFactory = null): RefereeFactory
    {
        $clone = tap(clone $this)->overwriteDefaults([
            'status' => RefereeStatus::RETIRED,
        ]);

        $now = now();
        $start = $now->copy()->subDays(2);
        $end = $now->copy()->subDays(1);

        $clone->employmentFactory = $employmentFactory ?? EmploymentFactory::new()->started($start)->ended($end);

        $clone->retirementFactory = $retirementFactory ?? RetirementFactory::new()->started($end);

        return $clone;
    }

    public function released(EmploymentFactory $employmentFactory = null): RefereeFactory
    {
        $clone = tap(clone $this)->overwriteDefaults([
            'status' => RefereeStatus::RELEASED,
        ]);

        $now = now();
        $start = $now->copy()->subDays(2);
        $end = $now->copy()->subDays(1);

        $clone->employmentFactory = $employmentFactory ?? EmploymentFactory::new()->started($start)->ended($end);

        return $clone;
    }

    public function suspended(EmploymentFactory $employmentFactory = null, SuspensionFactory $suspensionFactory = null): RefereeFactory
    {
        $clone = tap(clone $this)->overwriteDefaults([
            'status' => RefereeStatus::SUSPENDED,
        ]);

        $now = now();
        $start = $now->copy()->subDays(2);
        $end = $now->copy()->subDays(1);

        $clone = $clone->employ($employmentFactory ?? EmploymentFactory::new()->started($start));

        $clone->suspensionFactory = $suspensionFactory ?? SuspensionFactory::new()->started($end);

        return $clone;
    }

    public function injured(EmploymentFactory $employmentFactory = null, InjuryFactory $injuryFactory = null): RefereeFactory
    {
        $clone = tap(clone $this)->overwriteDefaults([
            'status' => RefereeStatus::INJURED,
        ]);

        $now = now();
        $start = $now->copy()->subDays(2);

        $clone = $clone->employ($employmentFactory ?? EmploymentFactory::new()->started($start));

        $clone->injuryFactory = $injuryFactory ?? InjuryFactory::new()->started($start->copy()->addDay());

        return $clone;
    }

    public function softDeleted($delete = true)
    {
        $clone = clone $this;
        $clone->softDeleted = $delete;

        return $clone;
    }
}
