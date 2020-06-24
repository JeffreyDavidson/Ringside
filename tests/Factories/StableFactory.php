<?php

namespace Tests\Factories;

use App\Enums\StableStatus;
use App\Models\Stable;
use Christophrumpel\LaravelFactoriesReloaded\BaseFactory;
use Faker\Generator as Faker;
use Illuminate\Support\Str;

class StableFactory extends BaseFactory
{
    /** @var ActivationFactory|null */
    public $activationFactory;

    /** @var RetirementFactory|null */
    public $retirementFactory;

    /** @var WrestlerFactory|null */
    public $wrestlerFactory;

    /** @var TagTeamFactory|null */
    public $tagTeamFactory;

    /** @var array|null */
    public $existingWrestlers;

    /** @var array|null */
    public $existingTagTeams;

    /** @var $softDeleted */
    public $softDeleted = false;

    protected string $modelClass = Stable::class;

    public function create(array $extra = []): Stable
    {
        $stable = parent::build($extra);

        if ($this->activationFactory) {
            $this->activationFactory->forStable($stable)->create();
        }

        if ($this->retirementFactory) {
            $this->retirementFactory->forStable($stable)->create();
        }

        if ($this->wrestlerFactory) {
            $this->wrestlerFactory->forStable($stable)->create();
        }

        if ($this->tagTeamFactory) {
            $this->tagTeamFactory->forStable($stable)->create();
        }

        $stable->save();

        $this->getMembers($stable);

        if ($this->softDeleted) {
            $stable->delete();
        }

        return $stable;
    }

    public function make(array $extra = []): Stable
    {
        return parent::build($extra, 'make');
    }

    public function getDefaults(Faker $faker): array
    {
        return [
            'name' => Str::title($faker->words(2, true)),
            'status' => StableStatus::__default,
        ];
    }

    public function futureActivation(ActivationFactory $activationFactory = null)
    {
        $clone = tap(clone $this)->overwriteDefaults([
            'status' => StableStatus::FUTURE_ACTIVATION
        ]);

        $clone->activationFactory = $activationFactory ?? ActivationFactory::new()->started(now()->addDays(2));

        return $clone;
    }

    public function unactivated()
    {
        return tap(clone $this)->overwriteDefaults([
            'status' => StableStatus::UNACTIVATED
        ]);
    }

    public function active(ActivationFactory $activationFactory = null)
    {
        $clone = clone $this;

        $clone->attributes['status'] = StableStatus::ACTIVE;

        $clone->activationFactory = $activationFactory ?? ActivationFactory::new();

        return $clone;
    }

    public function inactive(ActivationFactory $activationFactory = null)
    {
        $clone = clone $this;

        $clone->attributes['status'] = StableStatus::INACTIVE;

        $clone->activationFactory = $activationFactory ?? ActivationFactory::new()->started(now()->subMonths(3))->ended(now()->subDay(1));

        return $clone;
    }

    public function retired(ActivationFactory $activationFactory = null, RetirementFactory $retirementFactory = null)
    {
        $clone = clone $this;

        $clone->attributes['status'] = StableStatus::RETIRED;

        $clone->activationFactory = ActivationFactory::new()->started(now()->subMonths(1))->ended(now()->subDays(3));

        $clone->retirementFactory = $retirementFactory ?? RetirementFactory::new()->started(now()->subDays(3));

        return $clone;
    }

    public function withWrestlers(WrestlerFactory $wrestlerFactory = null)
    {
        $clone = clone $this;
        $clone->wrestlerFactory = $wrestlerFactory ?? WrestlerFactory::new()->bookable();

        return $clone;
    }

    public function withTagTeams(TagTeamFactory $tagTeamFactory = null)
    {
        $clone = clone $this;
        $clone->tagTeamFactory = $tagTeamFactory ?? TagTeamFactory::new()->bookable();

        return $clone;
    }

    private function getMembers($stable)
    {
        if ($this->existingWrestlers) {
            foreach ($this->existingWrestlers as $wrestler) {
                $wrestler->stables()->attach($stable);
            }
        } elseif ($this->wrestlerFactory) {
            $this->addWrestlerFactories($stable);
        } else {
            $this->generateTwoNewWrestlerFactories($stable);
        }

        if ($this->existingTagTeams) {
            foreach ($this->existingTagTeams as $tagTeam) {
                $tagTeam->stables()->attach($stable);
            }
        } elseif ($this->tagTeamFactory) {
            $this->addTagTeamFactories($stable);
        } else {
            $this->generateNewTagTeamFactory($stable);
        }

        return $this;
    }
}
