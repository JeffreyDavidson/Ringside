<?php

namespace Tests\Factories;

use App\Enums\TagTeamStatus;
use App\Models\Stable;
use App\Models\TagTeam;
use App\Models\Wrestler;
use Christophrumpel\LaravelFactoriesReloaded\BaseFactory;
use Faker\Generator as Faker;
use Illuminate\Support\Str;

class TagTeamFactory extends BaseFactory
{
    /** @var EmploymentFactory|null */
    public $employmentFactory;

    /** @var RetirementFactory|null */
    public $retirementFactory;

    /** @var SuspensionFactory|null */
    public $suspensionFactory;

    /** @var WrestlerFactory|null */
    public $wrestlerFactory;

    /** @var Stable */
    public $stable;

    /** @var array|null */
    public $existingWrestlers;

    /** @var */
    public $softDeleted = false;

    protected string $modelClass = TagTeam::class;

    public function create(array $extra = []): TagTeam
    {
        $tagTeam = parent::build($extra);

        if ($this->employmentFactory) {
            $this->employmentFactory->forTagTeam($tagTeam)->create();
        }

        if ($this->retirementFactory) {
            $this->retirementFactory->forTagTeam($tagTeam)->create();
        }

        if ($this->suspensionFactory) {
            $this->suspensionFactory->forTagTeam($tagTeam)->create();
        }

        if ($this->stable) {
            $tagTeam->stables()->attach($this->stable);
        }

        $tagTeam->save();

        $this->addWrestlers($tagTeam);

        if ($this->softDeleted) {
            $tagTeam->delete();
        }

        return $tagTeam;
    }

    public function make(array $extra = []): TagTeam
    {
        return parent::build($extra, 'make');
    }

    public function getDefaults(Faker $faker): array
    {
        return [
            'name' => Str::title($faker->words(2, true)),
            'signature_move' => Str::title($faker->words(4, true)),
            'status' => TagTeamStatus::__default,
        ];
    }

    public function employed(EmploymentFactory $employmentFactory = null)
    {
        $clone = clone $this;

        $clone->employmentFactory = $employmentFactory ?? EmploymentFactory::new()->started();

        return $clone;
    }

    public function bookable(EmploymentFactory $employmentFactory = null): self
    {
        $clone = tap(clone $this)->overwriteDefaults([
            'status' => TagTeamStatus::BOOKABLE,
        ]);

        $clone = $clone->employed($employmentFactory ?? $this->employmentFactory);

        $clone->existingWrestlers = WrestlerFactory::new()
            ->bookable($employmentFactory ?? $this->employmentFactory)
            ->times(2)
            ->create();

        return $clone;
    }

    public function pendingEmployment(EmploymentFactory $employmentFactory = null): self
    {
        $clone = tap(clone $this)->overwriteDefaults([
            'status' => TagTeamStatus::PENDING_EMPLOYMENT,
        ]);

        $startDate = EmploymentFactory::new()->started(now()->addDay(1));

        $clone = $clone->employed($employmentFactory ?? $startDate);

        $clone->wrestlerFactory = WrestlerFactory::new()
            ->pendingEmployment($employmentFactory ?? $startDate)
            ->times(2);

        return $clone;
    }

    public function unemployed(): self
    {
        $clone = tap(clone $this)->overwriteDefaults([
            'status' => TagTeamStatus::UNEMPLOYED,
        ]);

        $clone->wrestlerFactory = WrestlerFactory::new()
            ->unemployed()
            ->times(2);

        return $clone;
    }

    public function suspended(EmploymentFactory $employmentFactory = null, SuspensionFactory $suspensionFactory = null): self
    {
        $clone = tap(clone $this)->overwriteDefaults([
            'status' => TagTeamStatus::SUSPENDED,
        ]);

        $clone = $clone->employed($employmentFactory ?? $this->employmentFactory);

        $suspensionDefaultStartDate = $clone->employmentFactory->startDate->copy()->addDay();
        $clone->suspensionFactory = $suspensionFactory ?? SuspensionFactory::new()->started($suspensionDefaultStartDate);

        $clone->wrestlerFactory = WrestlerFactory::new()
            ->suspended($clone->employmentFactory, $clone->suspensionFactory)
            ->times(2);

        return $clone;
    }

    public function retired(EmploymentFactory $employmentFactory = null, RetirementFactory $retirementFactory = null): self
    {
        $clone = tap(clone $this)->overwriteDefaults([
            'status' => TagTeamStatus::RETIRED,
        ]);

        $now = now();
        $start = $now->copy()->subDays(3);
        $end = $now->copy()->subDays(1);

        $clone = $clone->employed($employmentFactory ?? EmploymentFactory::new()->started($start)->ended($end));

        $clone->retirementFactory = $retirementFactory ?? RetirementFactory::new()->started($end);

        $clone->wrestlerFactory = WrestlerFactory::new()
            ->retired($clone->employmentFactory, $clone->retirementFactory)
            ->times(2);

        return $clone;
    }

    public function released(EmploymentFactory $employmentFactory = null): self
    {
        $clone = tap(clone $this)->overwriteDefaults([
            'status' => TagTeamStatus::RELEASED,
        ]);

        $now = now();
        $start = $now->copy()->subDays(3);
        $end = $now->copy()->subDays(1);

        $clone->employmentFactory = $employmentFactory ?? EmploymentFactory::new()->started($start)->ended($end);

        $clone->wrestlerFactory = WrestlerFactory::new()
            ->released($clone->employmentFactory)
            ->times(2);

        return $clone;
    }

    public function forStable(Stable $stable)
    {
        $clone = clone $this;
        $clone->stable = $stable;

        return $clone;
    }

    public function withExistingWrestlers($wrestlers)
    {
        $clone = clone $this;

        $clone->existingWrestlers = $wrestlers;

        return $clone;
    }

    /**
     * This method is used as a backup to make sure there are always two wrestler factories that need to be created for
     * the tag team. Inside this function it will look at the tag team and create similar attributes for employment,
     * suspension, retirement, etc.
     *
     * @param  App\Models\TagTeam $tagTeam
     * @return void
     */
    private function generateTwoNewWrestlerFactories(TagTeam $tagTeam)
    {
        $wrestlers = [];

        for ($x = 1; $x <= 2; $x++) {
            $wrestlerCount = Wrestler::max('id') + 1;

            $wrestlers[] = WrestlerFactory::new()
                ->forTagTeam($tagTeam)
                ->create(['name' => 'Wrestler '.$wrestlerCount]);
        }

        return $wrestlers;
    }

    private function addWrestlers($tagTeam)
    {
        if ($this->existingWrestlers) {
            $numberOfExistingWrestlers = count($this->existingWrestlers);
            // dd($numberOfExistingWrestlers);

            if ($numberOfExistingWrestlers > 2) {
                // RETURN ERROR FOR TOO MANY WRESTLERS
            }

            $numberOfWrestlersToCreate = 2 - $numberOfExistingWrestlers;
            // dd($numberOfWrestlersToCreate);

            $createdWrestlers = [];

            for ($i = 0; $i < $numberOfWrestlersToCreate; $i++) {
                $wrestlerCount = Wrestler::max('id') + 1;

                $createdWrestlers[] = WrestlerFactory::new()
                    ->create(['name' => 'Wrestler '.$wrestlerCount]);
            }

            // dd($createdWrestlers);

            $tagTeamOfWrestlers = collect($this->existingWrestlers)->merge($createdWrestlers);

            if (count($tagTeamOfWrestlers) > 0) {
                foreach ($tagTeamOfWrestlers as $wrestler) {
                    $wrestler->tagTeams()->attach($tagTeam);
                }
            }
        } else {
            $this->generateTwoNewWrestlerFactories($tagTeam);
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
