<?php

namespace Tests\Factories;

use App\Enums\ManagerStatus;
use App\Models\Manager;
use App\Models\TagTeam;
use App\Models\Wrestler;
use Christophrumpel\LaravelFactoriesReloaded\BaseFactory;
use Faker\Generator as Faker;

class ManagerFactory extends BaseFactory
{
    /** @var EmploymentFactory|null */
    public $employmentFactory;

    /** @var RetirementFactory|null */
    public $retirementFactory;

    /** @var SuspensionFactory|null */
    public $suspensionFactory;

    /** @var InjuryFactory|null */
    public $injuryFactory;

    /** @var TagTeam */
    public $tagTeam;

    /** @var Wrestler */
    public $wrestler;

    /** @var $softDeleted */
    public $softDeleted = false;

    protected string $modelClass = Manager::class;

    public function create(array $extra = []): Manager
    {
        $manager = parent::build($extra);

        if ($this->employmentFactory) {
            $this->employmentFactory->forManager($manager)->create();
        }

        if ($this->retirementFactory) {
            $this->retirementFactory->forManager($manager)->create();
        }

        if ($this->suspensionFactory) {
            $this->suspensionFactory->forManager($manager)->create();
        }

        if ($this->injuryFactory) {
            $this->injuryFactory->forManager($manager)->create();
        }

        if ($this->wrestler) {
            $this->wrestler->currentManager()->attach($manager);
        }

        if ($this->tagTeam) {
            $this->tagTeam->currentManager()->attach($manager);
        }

        $manager->save();

        if ($this->softDeleted) {
            $manager->delete();
        }

        return $manager;
    }

    public function make(array $extra = []): Manager
    {
        return parent::build($extra, 'make');
    }

    public function getDefaults(Faker $faker): array
    {
        return [
            'first_name' => $faker->firstName,
            'last_name' => $faker->lastName,
            'status' => ManagerStatus::__default,
        ];
    }

    public function employ(EmploymentFactory $employmentFactory = null)
    {
        $clone = clone $this;

        $clone->employmentFactory = $employmentFactory ?? EmploymentFactory::new()->started();

        return $clone;
    }

    public function employed(EmploymentFactory $employmentFactory = null)
    {
        $clone = clone $this;

        $clone->employmentFactory = $employmentFactory ?? EmploymentFactory::new();

        return $clone;
    }

    public function available(): ManagerFactory
    {
        $clone = tap(clone $this)->overwriteDefaults([
            'status' => ManagerStatus::AVAILABLE,
        ]);

        $clone->employmentFactory = $employmentFactory ?? EmploymentFactory::new()->started(now());

        return $clone;
    }

    public function withFutureEmployment(): ManagerFactory
    {
        $clone = tap(clone $this)->overwriteDefaults([
            'status' => ManagerStatus::FUTURE_EMPLOYMENT,
        ]);

        $clone->employmentFactory = $employmentFactory ?? EmploymentFactory::new()->started(now()->addDay());

        return $clone;
    }

    public function unemployed(): ManagerFactory
    {
        return tap(clone $this)->overwriteDefaults([
            'status' => ManagerStatus::UNEMPLOYED,
        ]);
    }

    public function retired(EmploymentFactory $employmentFactory = null, RetirementFactory $retirementFactory = null): ManagerFactory
    {
        $clone = tap(clone $this)->overwriteDefaults([
            'status' => ManagerStatus::RETIRED,
        ]);

        $start = now()->subMonths(1);
        $end = now()->subDays(3);

        $clone->employmentFactory = $employmentFactory ?? EmploymentFactory::new()->started($start)->ended($end);

        $clone->retirementFactory = $retirementFactory ?? RetirementFactory::new()->started($end);

        return $clone;
    }

    public function released(EmploymentFactory $employmentFactory = null): ManagerFactory
    {
        $clone = tap(clone $this)->overwriteDefaults([
            'status' => ManagerStatus::RELEASED,
        ]);

        $start = now()->subMonths(1);
        $end = now()->subDays(3);

        $clone->employmentFactory = $employmentFactory ?? EmploymentFactory::new()->started($start)->ended($end);

        return $clone;
    }

    public function suspended(EmploymentFactory $employmentFactory = null, SuspensionFactory $suspensionFactory = null): ManagerFactory
    {
        $clone = tap(clone $this)->overwriteDefaults([
            'status' => ManagerStatus::SUSPENDED,
        ]);

        $now = now();
        $start = $now->copy()->subDays(2);
        $end = $now->copy()->subDays(1);

        $clone = $clone->employ($employmentFactory ?? EmploymentFactory::new()->started($start));

        $clone->suspensionFactory = $suspensionFactory ?? SuspensionFactory::new()->started($end);

        return $clone;
    }

    public function injured(EmploymentFactory $employmentFactory = null, InjuryFactory $injuryFactory = null): ManagerFactory
    {
        $clone = tap(clone $this)->overwriteDefaults([
            'status' => ManagerStatus::INJURED,
        ]);

        $now = now();
        $start = $now->copy()->subDays(2);

        $clone = $clone->employ($employmentFactory ?? EmploymentFactory::new()->started($start));

        $clone->injuryFactory = $injuryFactory ?? InjuryFactory::new()->started($start->copy()->addDay());

        return $clone;
    }

    public function forTagTeam(TagTeam $tagTeam)
    {
        $clone = clone $this;
        $clone->tagTeam = $tagTeam;

        return $clone;
    }

    public function forWrestler(Wrestler $wrestler)
    {
        $clone = clone $this;
        $clone->wrestler = $wrestler;

        return $clone;
    }

    public function softDeleted($delete = true)
    {
        $clone = clone $this;
        $clone->softDeleted = $delete;

        return $clone;
    }
}
