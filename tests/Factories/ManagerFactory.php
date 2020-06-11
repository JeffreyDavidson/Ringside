<?php

namespace Tests\Factories;

use App\Models\Manager;
use App\Models\TagTeam;
use App\Enums\ManagerStatus;
use Faker\Generator as Faker;
use Christophrumpel\LaravelFactoriesReloaded\BaseFactory;

class ManagerFactory extends BaseFactory
{
    protected string $modelClass = Manager::class;

    public function create(array $extra = []): Manager
    {
        return parent::build($extra);
    }

    public function make(array $extra = []): Manager
    {
        return parent::build($extra, 'make');
    }

    public function getDefaults(Faker $faker): array
    {
        return [
            'first_name' => $faker->firstName,
            'last_name' => $faker->lastName,
            'status' => ManagerStatus::PENDING_EMPLOYMENT,
        ];
    }

    public function available(): ManagerFactory
    {
        return tap(clone $this)->overwriteDefaults([
            'status' => ManagerStatus::AVAILABLE,
        ]);
    }

    public function pendingEmployment(): ManagerFactory
    {
        return tap(clone $this)->overwriteDefaults([
            'status' => ManagerStatus::PENDING_EMPLOYMENT,
        ]);
    }

    public function retired(): ManagerFactory
    {
        return tap(clone $this)->overwriteDefaults([
            'status' => ManagerStatus::RETIRED,
        ]);
    }

    public function suspended(): ManagerFactory
    {
        return tap(clone $this)->overwriteDefaults([
            'status' => ManagerStatus::SUSPENDED,
        ]);
    }

    public function injured(): ManagerFactory
    {
        return tap(clone $this)->overwriteDefaults([
            'status' => ManagerStatus::INJURED,
        ]);
    }

    public function forTagTeam(TagTeam $tagTeam)
    {
        $clone = clone $this;
        $clone->tagTeam = $tagTeam;

        return $clone;
    }
}
