<?php

namespace Database\Factories;

use App\Enums\StableStatus;
use App\Models\Stable;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class StableFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected string $modelClass = Stable::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition(): array
    {
        return [
            'name' => Str::title($this->faker->words(2, true)),
            'status' => StableStatus::__default,
        ];
    }

    public function withFutureActivation(): self
    {
        $clone = tap(clone $this)->overwriteDefaults([
            'status' => StableStatus::FUTURE_ACTIVATION,
        ]);

        $clone = $clone->withFactory(ActivationFactory::new()->started(Carbon::tomorrow()), 'activations', 1);

        return $clone;
    }

    public function unactivated()
    {
        return tap(clone $this)->overwriteDefaults([
            'status' => StableStatus::UNACTIVATED,
        ]);
    }

    public function active(): self
    {
        $clone = tap(clone $this)->overwriteDefaults([
            'status' => StableStatus::ACTIVE,
        ]);

        $clone = $clone->withFactory(ActivationFactory::new()->started(Carbon::yesterday()), 'activations', 1);
        $clone->withMembers();

        return $clone;
    }

    public function withMembers(): self
    {
        $clone = tap(clone $this);

        $clone = $clone->withFactory(WrestlerFactory::new()->bookable(), 'members', 1);
        $clone = $clone->withFactory(TagTeamFactory::new()->bookable(), 'members', 1);
        $clone = $clone->withFactory(ManagerFactory::new()->available(), 'members', 1);

        return $clone;
    }

    public function inactive(): self
    {
        $clone = tap(clone $this)->overwriteDefaults([
            'status' => StableStatus::INACTIVE,
        ]);

        $now = now();
        $start = $now->copy()->subDays(2);
        $end = $now->copy()->subDays(1);

        $clone = $clone->withFactory(ActivationFactory::new()->started($start)->ended($end), 'activations', 1);

        return $clone;
    }

    public function retired(): self
    {
        $clone = tap(clone $this)->overwriteDefaults([
            'status' => StableStatus::RETIRED,
        ]);

        $now = now();
        $start = $now->copy()->subDays(3);
        $end = $now->copy()->subDays(1);

        $clone = $clone->withFactory(ActivationFactory::new()->started($start)->ended($end), 'activations', 1);
        $clone = $clone->withFactory(RetirementFactory::new()->started($end), 'retirements', 1);
        $clone = $this->withMembers();

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

    public function softDeleted($delete = true)
    {
        $clone = clone $this;
        $clone->softDeleted = $delete;

        return $clone;
    }
}
