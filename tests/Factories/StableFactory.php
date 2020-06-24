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
            // dd($this->wrestlerFactory);
            // foreach ($this->wrestlerFactory->getFactories() as $wrestlerFactory) {
            //     $wrestlerFactory->forStable($stable)->create();
            // }
            $this->wrestlerFactory->forStable($stable)->create();
        }

        if ($this->tagTeamFactory) {
            $this->tagTeamFactory->forStable($stable)->create();
        }

        $stable->save();

        $this->generateMembers($stable);

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

    public function activate(ActivationFactory $activationFactory = null)
    {
        $clone = clone $this;

        $clone->activationFactory = $activationFactory ?? ActivationFactory::new()->started();

        return $clone;
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
        $clone = tap(clone $this)->overwriteDefaults([
            'status' => StableStatus::ACTIVE,
        ]);

        $clone = $clone->activate($activationFactory ?? null);

        $clone->wrestlerFactory = WrestlerFactory::new()
            ->employ(EmploymentFactory::new()->started($activationFactory->startDate ?? null));

        return $clone;
    }

    public function inactive(ActivationFactory $activationFactory = null)
    {
        $clone = tap(clone $this)->overwriteDefaults([
            'status' => StableStatus::INACTIVE,
        ]);

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

    public function withExistingWrestlers($wrestlers)
    {
        $clone = clone $this;

        $clone->existingWrestlers = collect($wrestlers);

        return $clone;
    }

    public function withExistingTagTeams($tagTeams)
    {
        $clone = clone $this;

        $clone->existingTagTeams = collect($tagTeams);

        return $clone;
    }

    private function generateMembers($stable)
    {
        $existingWrestlersCount = $this->existingWrestlers ? count($this->existingWrestlers) : 0;
        $wrestlersFactoriesCount = $this->wrestlerFactory ? count($this->wrestlerFactory->getFactories()) : 0;
        $possibleWrestlersCount = $existingWrestlersCount + $wrestlersFactoriesCount;

        $existingTagTeamsCount = $this->existingTagTeams ? count($this->existingTagTeams) : 0;
        $tagTeamFactoriesCount = $this->tagTeamFactory ? count($this->tagTeamFactory->getFactories()) : 0;
        $possibleTagTeamsCount = $existingTagTeamsCount + $tagTeamFactoriesCount;

        $totalPossibleMembersCount = $possibleWrestlersCount + $possibleTagTeamsCount * 2;

        if ($totalPossibleMembersCount >= 3) {
            // There are the minimum requirements for members of a stable.
            dd('enough members');
        } else {
            $wrestlersToAddToStable = collect();
            // We know by hitting the else that there isn't enough members for this stable so we know we need to create them and add them to the stable or if they already exist then just add them.
            if ($existingWrestlersCount >= 1) {
                $wrestlersToAddToStable[] = $this->existingWrestlers;
            } elseif ($wrestlersFactoriesCount >= 1) {
                foreach ($this->wrestlerFactory->getFactories() as $wrestlerFactory) {
                    $wrestlersToAddToStable[] = $wrestlerFactory->create();
                }
            } else {
                $createdWrestlers = WrestlerFactory::new()->times(3)->create();
                // dd($createdWrestlers);
                $newCollection = $wrestlersToAddToStable->concat($createdWrestlers);
            }

            foreach ($newCollection as $wrestler) {
                // $wrestler->stables()->save($stable, ['joined_at' => now()]);
                $wrestler->stables()->attach($stable, ['joined_at' => now()]);
            }
        }

        return $this;
    }
}
