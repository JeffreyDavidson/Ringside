<?php

namespace Tests\Factories;

use App\Enums\TagTeamStatus;
use App\Models\TagTeam;
use App\Models\Wrestler;
use Carbon\Carbon;
use Christophrumpel\LaravelFactoriesReloaded\BaseFactory;
use Faker\Generator as Faker;
use Illuminate\Support\Str;

class TagTeamFactory extends BaseFactory
{
    protected string $modelClass = TagTeam::class;

    public $existingWrestlers;

    public $softDeleted = false;

    public function create(array $extra = []): TagTeam
    {
        $tagTeam = parent::build($extra);

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

    public function withWrestlers(WrestlerFactory $wrestlerFactory = null)
    {
        $clone = clone $this;
        $clone->existingWrstlers = $wrestlerFactory ?? WrestlerFactory::new()->times(2);

        return $clone;
    }

    public function withExistingWrestlers($wrestlers)
    {
        $clone = clone $this;

        $clone->existingWrestlers = $wrestlers;

        return $clone;
    }

    public function bookable()
    {
        $clone = tap(clone $this)->overwriteDefaults([
            'status' => TagTeamStatus::BOOKABLE,
        ]);

        $clone = $clone->withFactory(EmploymentFactory::new()->started(Carbon::yesterday()), 'employments', 1);

        return $clone;
    }

    public function addExistingWrestlersToTagTeam($tagTeam)
    {
        $numberOfExistingWrestlers = count($this->existingWrestlers);

        if ($numberOfExistingWrestlers > 2) {
            // RETURN ERROR FOR TOO MANY WRESTLERS
        }

        $numberOfWrestlersToCreate = 2 - $numberOfExistingWrestlers;

        $createdWrestlers = [];
        if ($numberOfWrestlersToCreate) {
            $createdWrestlers = $this->createWrestlers($numberOfExistingWrestlers);
        }

        $tagTeamOfWrestlers = collect($this->existingWrestlers)->merge($createdWrestlers);

        if (count($tagTeamOfWrestlers) > 0) {
            foreach ($tagTeamOfWrestlers as $wrestler) {
                $wrestler->tagTeams()->attach($tagTeam);
            }
        }

        return $tagTeam;
    }

    public function createWrestlers($numberOfWrestlersToCreate)
    {
        $createdWrestlers = [];
        for ($i = 0; $i < $numberOfWrestlersToCreate; $i++) {
            $wrestlerCount = Wrestler::max('id') + 1;

            $createdWrestlers[] = WrestlerFactory::new()->create(['name' => 'Wrestler '.$wrestlerCount]);
        }

        return $createdWrestlers;
    }

    private function addWrestlers($tagTeam)
    {
        if ($this->existingWrestlers) {
            $wrestlers = $this->addExistingWrestlersToTagTeam($tagTeam);
        } else {
            $wrestlers = $this->createWrestlers(2);
        }

        $tagTeam->addWrestlers($wrestlers);

        return $this;
    }

    public function softDeleted($delete = true)
    {
        $clone = clone $this;
        $clone->softDeleted = $delete;

        return $clone;
    }
}
