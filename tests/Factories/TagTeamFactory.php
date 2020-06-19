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

        if ($this->wrestlerFactory) {
            for ($i = 1; $i <= count($this->wrestlerFactory->getFactories()); $i++) {
                $wrestlerCount = Wrestler::max('id') + 1;
                WrestlerFactory::new()
                        ->forTagTeam($tagTeam)
                        ->retired($this->employmentFactory, $this->retirementFactory)
                        ->create(['name' => 'Wrestler '. $wrestlerCount]);
            }
        }

        $tagTeam->save();

        if ($this->existingWrestlers) {
            foreach ($this->existingWrestlers as $wrestler) {
                $wrestler->tagTeamHistory()->attach($tagTeam);
            }
        }

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
            'status' => TagTeamStatus::PENDING_EMPLOYMENT,
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

        return $clone;
    }

    public function pendingEmployment(EmploymentFactory $employmentFactory = null): TagTeamFactory
    {
        $clone = tap(clone $this)->overwriteDefaults([
            'status' => TagTeamStatus::PENDING_EMPLOYMENT,
        ]);

        $clone = $clone->employed($employmentFactory ?? $this->employmentFactory);

        $clone = $clone->withWrestlers($wrestlerFactory ?? $this->wrestlerFactory);

        return $clone;
    }

    public function unemployed(): TagTeamFactory
    {
        return tap(clone $this)->overwriteDefaults([
            'status' => TagTeamStatus::UNEMPLOYED,
        ]);
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
}
