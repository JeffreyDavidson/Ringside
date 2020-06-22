<?php

namespace Tests\Factories;

use App\Enums\TagTeamStatus;
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

    /** @var array|null */
    public $existingWrestlers;

    /** @var $softDeleted */
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

        $tagTeam->save();

        // dd($tagTeam->wrestlers->count());
        // dd(count($this->wrestlerFactory->getFactories()));

        if ($this->existingWrestlers) {
            // Both wrestlers are already created and just need assigned as a tag team.
            foreach ($this->existingWrestlers as $wrestler) {
                $wrestler->tagTeams()->attach($tagTeam);
            }
        } elseif ($this->wrestlerFactory) {
            $this->addWrestlerFactories($tagTeam);
        } else {
            $this->generateTwoNewWrestlerFactories($tagTeam);
        }

        // dd($tagTeam->wrestlers);
        // dd($tagTeam->wrestlers->count());

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

        $clone->employmentFactory = $employmentFactory ?? EmploymentFactory::new();

        return $clone;
    }

    public function bookable(EmploymentFactory $employmentFactory = null): TagTeamFactory
    {
        $clone = tap(clone $this)->overwriteDefaults([
            'status' => TagTeamStatus::BOOKABLE,
        ]);

        $clone = $clone->employed($employmentFactory ?? $this->employmentFactory);

        $clone->wrestlerFactory = WrestlerFactory::new()
            ->bookable($employmentFactory ?? $this->employmentFactory)
            ->times(2);

        return $clone;
    }

    public function pendingEmployment(EmploymentFactory $employmentFactory = null): TagTeamFactory
    {
        $clone = tap(clone $this)->overwriteDefaults([
            'status' => TagTeamStatus::PENDING_EMPLOYMENT,
        ]);

        $clone = $clone->employed($employmentFactory ?? $this->employmentFactory);

        $clone->wrestlerFactory = WrestlerFactory::new()
            ->pendingEmployment($employmentFactory ?? $this->employmentFactory)
            ->times(2);

        return $clone;
    }

    public function unemployed(): TagTeamFactory
    {
        $clone = tap(clone $this)->overwriteDefaults([
            'status' => TagTeamStatus::UNEMPLOYED,
        ]);

        $clone->wrestlerFactory = WrestlerFactory::new()
            ->unemployed()
            ->times(2);

        return $clone;
    }

    public function suspended(EmploymentFactory $employmentFactory = null, SuspensionFactory $suspensionFactory = null): TagTeamFactory
    {
        $clone = tap(clone $this)->overwriteDefaults([
            'status' => TagTeamStatus::SUSPENDED,
        ]);

        $clone = $clone->employed($employmentFactory ?? $this->employmentFactory);

        $clone->suspensionFactory = $suspensionFactory ?? SuspensionFactory::new();

        $clone->wrestlerFactory = WrestlerFactory::new()
            ->suspended($employmentFactory ?? $this->employmentFactory, $suspensionFactory ?? $this->suspensionFactory)
            ->times(2);

        return $clone;
    }

    public function retired(EmploymentFactory $employmentFactory = null, RetirementFactory $retirementFactory = null): TagTeamFactory
    {
        $clone = tap(clone $this)->overwriteDefaults([
            'status' => TagTeamStatus::RETIRED,
        ]);

        $clone = $clone->employed($employmentFactory ?? $this->employmentFactory);

        $clone->retirementFactory = $retirementFactory ?? RetirementFactory::new();

        $clone->wrestlerFactory = WrestlerFactory::new()
            ->retired($employmentFactory ?? $this->employmentFactory, $retirementFactory ?? $this->retirementFactory)
            ->times(2);

        return $clone;
    }

    public function released(EmploymentFactory $employmentFactory = null): TagTeamFactory
    {
        $clone = tap(clone $this)->overwriteDefaults([
            'status' => TagTeamStatus::RELEASED,
        ]);

        $start = now()->subMonths(1);
        $end = now()->subDays(3);

        $clone->employmentFactory = $employmentFactory ?? EmploymentFactory::new()->started($start)->ended($end);

        $clone->retirementFactory = $retirementFactory ?? RetirementFactory::new();

        $clone->wrestlerFactory = WrestlerFactory::new()
            ->released($employmentFactory ?? $this->employmentFactory)
            ->times(2);

        return $clone;
    }

    public function withWrestlers(WrestlerFactory $wrestlerFactory = null)
    {
        $clone = clone $this;
        $clone->wrestlerFactory = $wrestlerFactory ?? WrestlerFactory::new()->times(2);

        return $clone;
    }

    public function withExistingWrestlers(array $wrestlers)
    {
        $clone->existingWrestlers = $wrestlers;

        return $clone;
    }

    /**
     * This method is used as a to add the already prepared wrestler factories to a tag team. Each wrestler factory
     * should share the same employment,suspension, retirement, etc.
     *
     * @param  App\Models\TagTeam $tagTeam
     * @return void
     */
    private function addWrestlerFactories(TagTeam $tagTeam)
    {
        foreach ($this->wrestlerFactory->getFactories() as $wrestlerFactory) {
            // dd($wrestlerFactory);
            // dd(Wrestler::count());
            $wrestlerCount = Wrestler::max('id') + 1;

            $wrestlerFactory->forTagTeam($tagTeam)->create(['name' => 'Wrestler '. $wrestlerCount]);
        }
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

        for ($x = 1; $x <= 2; $x++) {
            $wrestlerCount = Wrestler::max('id') + 1;
            $wrestlerFactory = WrestlerFactory::new();

            $wrestlerFactory->forTagTeam($tagTeam)->create(['name' => 'Wrestler '. $wrestlerCount]);
        }

        // dd(Wrestler::count());
        // dd(Wrestler::with('tagTeams')->get()->toArray());
        // dd($tagTeam->wrestlers->toArray());
    }
}
