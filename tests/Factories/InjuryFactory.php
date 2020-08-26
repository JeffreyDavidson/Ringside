<?php

namespace Tests\Factories;

use App\Models\Injury;
use Carbon\Carbon;
use Christophrumpel\LaravelFactoriesReloaded\BaseFactory;
use Faker\Generator as Faker;

class InjuryFactory extends BaseFactory
{
    protected string $modelClass = Injury::class;

    /**
     * @param string|Carbon $startDate
     */
    public function started($startDate = 'now'): self
    {
        return tap(clone $this)->overwriteDefaults([
            'started_at' => $startDate instanceof Carbon ? $startDate : new Carbon($startDate),
        ]);
    }

    /**
     * @param string|Carbon $endDate
     */
    public function ended($endDate = 'now'): self
    {
        return tap(clone $this)->overwriteDefaults([
            'ended_at' => $endDate instanceof Carbon ? $endDate : new Carbon($endDate),
        ]);
    }

    public function create(array $extra = []): Injury
    {
        return parent::build($extra);
    }

    public function make(array $extra = []): Injury
    {
        return parent::build($extra, 'make');
    }

    public function getDefaults(Faker $faker): array
    {
        return [];
    }
}
