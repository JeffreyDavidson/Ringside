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
    /** @var SuspensionFactory|null */
    public $suspensionFactory;
    /** @var InjuryFactory|null */
    public $injuryFactory;
    /** @var RetirementFactory|null */
    public $retirementFactory;
    public $softDeleted = false;
    protected $factoriesToClone = [
        'employmentFactory',
        'suspensionFactory',
        'injuryFactory',
        'retirementFactory',
    ];

    public function pendingEmployment(EmploymentFactory $employmentFactory = null)
    {
        $clone = clone $this;
        $clone->attributes['status'] = RefereeStatus::PENDING_EMPLOYMENT;
        // We set these to null since we can't be pending employment if they're set
        $clone->employmentFactory = $employmentFactory ?? EmploymentFactory::new()->started(now()->addDays(2));
        $clone->suspensionFactory = null;
        $clone->injuryFactory = null;
        $clone->retirementFactory = null;

        return $clone;
    }

    public function employed(EmploymentFactory $employmentFactory = null)
    {
        $clone = clone $this;
        $clone->employmentFactory = $employmentFactory ?? EmploymentFactory::new();

        return $clone;
    }

    public function unemployed()
    {
        $clone = clone $this;
        $clone->attributes['status'] = RefereeStatus::UNEMPLOYED;
        $clone->employmentFactory = null;

        return $clone;
    }

    public function bookable(EmploymentFactory $employmentFactory = null)
    {
        $clone = clone $this;
        $clone->attributes['status'] = RefereeStatus::BOOKABLE;
        $clone = $clone->employed($employmentFactory ?? $this->employmentFactory);
        // We set these to null since a referee cannot be bookable if any of these exist
        $clone->suspensionFactory = null;
        $clone->injuryFactory = null;
        $clone->retirementFactory = null;

        return $clone;
    }

    public function injured(InjuryFactory $injuryFactory = null, EmploymentFactory $employmentFactory = null)
    {
        $clone = clone $this;
        $clone->attributes['status'] = RefereeStatus::INJURED;
        $clone->injuryFactory = $injuryFactory ?? InjuryFactory::new();
        // We set the employment factory since a wrestler must be employed to retire
        $clone = $clone->employed($employmentFactory ?? $this->employmentFactory);

        return $clone;
    }

    public function suspended(SuspensionFactory $suspensionFactory = null, EmploymentFactory $employmentFactory = null)
    {
        $clone = clone $this;
        $clone->attributes['status'] = RefereeStatus::SUSPENDED;
        $clone = $clone->employed($employmentFactory ?? $this->employmentFactory);

        $clone->suspensionFactory = $suspensionFactory ?? $this->suspensionFactory ?? SuspensionFactory::new();

        return $clone;
    }

    public function retired(RetirementFactory $retirementFactory = null, EmploymentFactory $employmentFactory = null)
    {
        $clone = clone $this;
        $clone->attributes['status'] = RefereeStatus::RETIRED;
        $clone->retirementFactory = $retirementFactory ?? RetirementFactory::new();
        // We set the employment factory since a wrestler must be employed to retire
        $clone = $clone->employed($employmentFactory ?? $this->employmentFactory);

        return $clone;
    }

    public function create($attributes = [])
    {
        return $this->make(function ($attributes) {
            $referee = Referee::create($this->resolveAttributes($attributes));

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
        }, $attributes);
    }

    public function getDefaults(Faker $faker)
    {
        return [
            'first_name' => $faker->firstName,
            'last_name' => $faker->lastName,
            'status' => RefereeStatus::__default,
        ];
    }
}
