<?php

namespace Tests\Factories;

use App\Enums\ManagerStatus;
use App\Models\Manager;
use App\Models\TagTeam;
use Christophrumpel\LaravelFactoriesReloaded\BaseFactory;
use Faker\Generator as Faker;

class ManagerFactory extends BaseFactory
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

    public function forTagTeam(TagTeam $tagTeam)
    {
        $clone = clone $this;
        $clone->tagTeam = $tagTeam;

        return $clone;
    }

    public function pendingEmployment()
    {
        $clone = clone $this;
        $clone->attributes['status'] = ManagerStatus::PENDING_EMPLOYMENT;
        // We set these to null since we can't be pending employment if they're set
        $clone->employmentFactory = $employmentFactory ?? EmploymentFactory::new()->started(now()->addDays(2));
        $clone->suspensionFactory = null;
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
        $clone->attributes['status'] = ManagerStatus::UNEMPLOYED;
        $clone->employmentFactory = null;

        return $clone;
    }

    public function available(EmploymentFactory $employmentFactory = null)
    {
        $clone = clone $this;
        $clone->attributes['status'] = ManagerStatus::AVAILABLE;
        $clone = $clone->employed($employmentFactory ?? $this->employmentFactory);
        // We set these to null since a TagTeam cannot be bookable if any of these exist
        $clone->suspensionFactory = null;
        $clone->injuryFactory = null;
        $clone->retirementFactory = null;

        return $clone;
    }

    public function injured(InjuryFactory $injuryFactory = null, EmploymentFactory $employmentFactory = null)
    {
        $clone = clone $this;
        $clone->attributes['status'] = ManagerStatus::INJURED;
        $clone->injuryFactory = $injuryFactory ?? InjuryFactory::new();
        // We set the employment factory since a wrestler must be employed to retire
        $clone = $clone->employed($employmentFactory ?? $this->employmentFactory);

        return $clone;
    }

    public function suspended(SuspensionFactory $suspensionFactory = null, EmploymentFactory $employmentFactory = null)
    {
        $clone = clone $this;
        $clone->attributes['status'] = ManagerStatus::SUSPENDED;
        $clone = $clone->employed($employmentFactory ?? $this->employmentFactory);

        $clone->suspensionFactory = $suspensionFactory ?? $this->suspensionFactory ?? SuspensionFactory::new();

        return $clone;
    }

    public function retired(RetirementFactory $retirementFactory = null, EmploymentFactory $employmentFactory = null)
    {
        $clone = clone $this;
        $clone->attributes['status'] = ManagerStatus::RETIRED;
        $clone->retirementFactory = $retirementFactory ?? RetirementFactory::new();
        // We set the employment factory since a wrestler must be employed to retire
        $clone = $clone->employed($employmentFactory ?? $this->employmentFactory);

        return $clone;
    }

    public function create($attributes = [])
    {
        return $this->make(function ($attributes) {
            $manager = Manager::create($this->resolveAttributes($attributes));

            if ($this->employmentFactory) {
                $this->employmentFactory->forManager($manager)->create();
            }

            if ($this->suspensionFactory) {
                $this->suspensionFactory->forManager($manager)->create();
            }

            if ($this->retirementFactory) {
                $this->retirementFactory->forManager($manager)->create();
            }

            if ($this->injuryFactory) {
                $this->injuryFactory->forManager($manager)->create();
            }

            $manager->save();

            if ($this->softDeleted) {
                $manager->delete();
            }

            return $manager;
        }, $attributes);
    }

    public function getDefaults(Faker $faker)
    {
        return [
            'first_name' => $faker->firstName,
            'last_name' => $faker->lastName,
            'status' => ManagerStatus::__default,
        ];
    }
}
