<?php

namespace Tests\Factories;

use Carbon\Carbon;
use App\Models\Manager;
use App\Models\TagTeam;
use App\Models\Wrestler;
use App\Enums\ManagerStatus;
use Faker\Generator as Faker;
use Christophrumpel\LaravelFactoriesReloaded\BaseFactory;

class ManagerFactory extends BaseFactory
{
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

    public function available(): self
    {
        $clone = tap(clone $this)->overwriteDefaults([
            'status' => ManagerStatus::AVAILABLE,
        ]);

        $clone = $clone->withFactory(EmploymentFactory::new()->started(Carbon::yesterday()), 'employments', 1);

        return $clone;
    }

    public function withFutureEmployment(): self
    {
        $clone = tap(clone $this)->overwriteDefaults([
            'status' => ManagerStatus::FUTURE_EMPLOYMENT,
        ]);

        $clone = $clone->withFactory(EmploymentFactory::new()->started(Carbon::tomorrow()), 'employments', 1);

        return $clone;
    }

    public function unemployed(): self
    {
        return tap(clone $this)->overwriteDefaults([
            'status' => ManagerStatus::UNEMPLOYED,
        ]);
    }

    public function retired(): self
    {
        $clone = tap(clone $this)->overwriteDefaults([
            'status' => ManagerStatus::RETIRED,
        ]);

        $start = now()->subMonths(1);
        $end = now()->subDays(3);

        $clone = $clone->withFactory(EmploymentFactory::new()->started($start)->ended($end), 'employments', 1);
        $clone = $clone->withFactory(RetirementFactory::new()->started($end), 'retirements', 1);

        return $clone;
    }

    public function released(): self
    {
        $clone = tap(clone $this)->overwriteDefaults([
            'status' => ManagerStatus::RELEASED,
        ]);

        $start = now()->subMonths(1);
        $end = now()->subDays(3);

        $clone = $clone->withFactory(EmploymentFactory::new()->started($start)->ended($end), 'employments', 1);

        return $clone;
    }

    public function suspended(): self
    {
        $clone = tap(clone $this)->overwriteDefaults([
            'status' => ManagerStatus::SUSPENDED,
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
            'status' => ManagerStatus::INJURED,
        ]);

        $now = now();
        $start = $now->copy()->subDays(2);

        $clone = $clone->withFactory(EmploymentFactory::new()->started($start), 'employments', 1);
        $clone = $clone->withFactory(InjuryFactory::new()->started($start->copy()->addDay()), 'injuries', 1);

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
