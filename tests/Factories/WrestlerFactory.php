<?php

namespace Tests\Factories;

use App\Enums\WrestlerStatus;
use App\Models\Stable;
use App\Models\TagTeam;
use App\Models\Wrestler;
use Carbon\Carbon;
use Christophrumpel\LaravelFactoriesReloaded\BaseFactory;
use Faker\Generator as Faker;
use Illuminate\Support\Str;

class WrestlerFactory extends BaseFactory
{
    /** @var TagTeam */
    public $tagTeam;

    /** @var Stable */
    public $stable;

    /** @var */
    public $softDeleted = false;

    protected string $modelClass = Wrestler::class;

    public function create(array $extra = []): Wrestler
    {
        $wrestler = $this->build($extra);

        if ($this->softDeleted) {
            $wrestler->delete();
        }

        return $wrestler;
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

    public function bookable(): self
    {
        $clone = tap(clone $this)->overwriteDefaults([
            'status' => WrestlerStatus::BOOKABLE,
        ]);

        $clone = $clone->withFactory(EmploymentFactory::new()->started(Carbon::yesterday()), 'employments', 1);

        return $clone;
    }

    public function withFutureEmployment(): self
    {
        $clone = tap(clone $this)->overwriteDefaults([
            'status' => WrestlerStatus::FUTURE_EMPLOYMENT,
        ]);

        $clone = $clone->withFactory(EmploymentFactory::new()->started(Carbon::tomorrow()), 'employments', 1);

        return $clone;
    }

    public function unemployed(): self
    {
        return tap(clone $this)->overwriteDefaults([
            'status' => WrestlerStatus::UNEMPLOYED,
        ]);
    }

    public function retired(): self
    {
        $clone = tap(clone $this)->overwriteDefaults([
            'status' => WrestlerStatus::RETIRED,
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
            'status' => WrestlerStatus::RELEASED,
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
            'status' => WrestlerStatus::SUSPENDED,
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
            'status' => WrestlerStatus::INJURED,
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

        $clone = $clone->withFactory(TagTeamFactory::new(), 'tagteams', 1);

        return $clone;
    }

    public function onATagTeam()
    {
        $clone = clone $this;

        $clone = $clone->withFactory(TagTeamFactory::new(), 'tagteams', 1);

        return $clone;
    }

    public function inAStable()
    {
        $clone = clone $this;

        $clone = $clone->withFactory(StableFactory::new()->joined(Carbon::now()), 'stables', 1);

        return $clone;
    }

    public function forStable(Stable $stable)
    {
        $clone = clone $this;
        $clone->stable = $stable;

        return $clone;
    }

    public function softDeleted($delete = true)
    {
        $clone = clone $this;
        $clone->softDeleted = $delete;

        return $clone;
    }
}
