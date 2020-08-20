<?php

namespace Tests\Factories;

use App\Models\Stable;
use App\Models\TagTeam;
use App\Models\Wrestler;
use App\Enums\WrestlerStatus;
use Illuminate\Support\Str;
use Faker\Generator as Faker;
use Christophrumpel\LaravelFactoriesReloaded\BaseFactory;

class WrestlerFactory extends BaseFactory
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

    /** @var Stable */
    public $stable;

    /** @var $softDeleted */
    public $softDeleted = false;

    protected string $modelClass = Wrestler::class;

    public function create(array $extra = []): Wrestler
    {
        $wrestler = parent::build($extra);

        if ($this->employmentFactory) {
            $this->employmentFactory->forWrestler($wrestler)->create();
        }

        if ($this->retirementFactory) {
            $this->retirementFactory->forWrestler($wrestler)->create();
        }

        if ($this->suspensionFactory) {
            $this->suspensionFactory->forWrestler($wrestler)->create();
        }

        if ($this->injuryFactory) {
            $this->injuryFactory->forWrestler($wrestler)->create();
        }

        if ($this->tagTeam) {
            $wrestler->tagTeams()->attach($this->tagTeam);
        }

        if ($this->stable) {
            $wrestler->stables()->attach($this->stable, ['joined_at' => now()]);
        }

        $wrestler->save();

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

    public function employ(EmploymentFactory $employmentFactory = null)
    {
        $clone = clone $this;

        $clone->employmentFactory = $employmentFactory ?? EmploymentFactory::new()->started();

        return $clone;
    }

    public function bookable(EmploymentFactory $employmentFactory = null): WrestlerFactory
    {
        $clone = tap(clone $this)->overwriteDefaults([
            'status' => WrestlerStatus::BOOKABLE,
        ]);

        $clone = $clone->employ($employmentFactory ?? null);

        return $clone;
    }

    public function withFutureEmployment(EmploymentFactory $employmentFactory = null): WrestlerFactory
    {
        $clone = tap(clone $this)->overwriteDefaults([
            'status' => WrestlerStatus::FUTURE_EMPLOYMENT,
        ]);

        $clone = $clone->employ($employmentFactory ?? EmploymentFactory::new()->started(now()->addDay(1)));

        return $clone;
    }

    public function unemployed(): WrestlerFactory
    {
        return tap(clone $this)->overwriteDefaults([
            'status' => WrestlerStatus::UNEMPLOYED,
        ]);
    }

    public function retired(EmploymentFactory $employmentFactory = null, RetirementFactory $retirementFactory = null): WrestlerFactory
    {
        $clone = tap(clone $this)->overwriteDefaults([
            'status' => WrestlerStatus::RETIRED,
        ]);

        $now = now();
        $start = $now->copy()->subDays(2);
        $end = $now->copy()->subDays(1);

        $clone = $clone->employ($employmentFactory ?? EmploymentFactory::new()->started($start)->ended($end));

        $clone->retirementFactory = $retirementFactory ?? RetirementFactory::new()->started($end);

        return $clone;
    }

    public function released(EmploymentFactory $employmentFactory = null): WrestlerFactory
    {
        $clone = tap(clone $this)->overwriteDefaults([
            'status' => WrestlerStatus::RELEASED,
        ]);

        $now = now();
        $start = $now->copy()->subDays(2);
        $end = $now->copy()->subDays(1);

        $clone = $clone->employ($employmentFactory ?? EmploymentFactory::new()->started($start)->ended($end));

        return $clone;
    }

    public function suspended(EmploymentFactory $employmentFactory = null, SuspensionFactory $suspensionFactory = null): WrestlerFactory
    {
        $clone = tap(clone $this)->overwriteDefaults([
            'status' => WrestlerStatus::SUSPENDED,
        ]);

        $now = now();
        $start = $now->copy()->subDays(2);
        $end = $now->copy()->subDays(1);

        $clone = $clone->employ($employmentFactory ?? EmploymentFactory::new()->started($start));

        $clone->suspensionFactory = $suspensionFactory ?? SuspensionFactory::new()->started($end);

        return $clone;
    }

    public function injured(EmploymentFactory $employmentFactory = null, InjuryFactory $injuryFactory = null): WrestlerFactory
    {
        $clone = tap(clone $this)->overwriteDefaults([
            'status' => WrestlerStatus::INJURED,
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
