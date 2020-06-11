<?php

namespace Tests\Factories;

use App\Enums\TagTeamStatus;
use App\Models\TagTeam;
use App\Models\Wrestler;
use Carbon\Carbon;
use Christophrumpel\LaravelFactoriesReloaded\BaseFactory;
use Faker\Generator as Faker;
class TagTeamFactory extends BaseFactory
{

    protected string $modelClass = TagTeam::class;

    public function create(array $extra = []): TagTeam
    {
        return parent::build($extra);
    }

    public function make(array $extra = []): TagTeam
    {
        return parent::build($extra, 'make');
    }

    public function getDefaults(Faker $faker): array
    {
        return [
            'name' => $faker->words(2, true),
            'signature_move' => $faker->words(4, true),
            'status' => TagTeamStatus::PENDING_EMPLOYMENT,
        ];

    }

    public function bookable(): TagTeamFactory
    {
        return tap(clone $this)->overwriteDefaults([
            'status' => TagTeamStatus::BOOKABLE,
        ]);
    }

    public function pendingEmployment(): TagTeamFactory
    {
        return tap(clone $this)->overwriteDefaults([
            'status' => TagTeamStatus::PENDING_EMPLOYMENT,
        ]);
    }

    public function suspended(): TagTeamFactory
    {
        return tap(clone $this)->overwriteDefaults([
            'status' => TagTeamStatus::SUSPENDED,
        ]);
    }

    public function retired(): TagTeamFactory
    {
        return tap(clone $this)->overwriteDefaults([
            'status' => TagTeamStatus::RETIRED,
        ]);
    }
}

