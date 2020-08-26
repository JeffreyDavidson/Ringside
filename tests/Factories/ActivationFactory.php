<?php

namespace Tests\Factories;

use Carbon\Carbon;
use App\Models\Title;
use App\Models\Stable;
use App\Models\Activation;
use Faker\Generator as Faker;
use Illuminate\Support\Collection;
use Christophrumpel\LaravelFactoriesReloaded\BaseFactory;

class ActivationFactory extends BaseFactory
{
    protected string $modelClass = Activation::class;

    /**
     * @param string|Carbon $startDate
     */
    public function started($startDate = 'now')
    {
        return tap(clone $this)->overwriteDefaults([
            'started_at' => $startDate instanceof Carbon ? $startDate : new Carbon($startDate),
        ]);
    }

    /**
     * @param string|Carbon $endDate
     */
    public function ended($endDate = 'now')
    {
        return tap(clone $this)->overwriteDefaults([
            'ended_at' => $endDate instanceof Carbon ? $endDate : new Carbon($endDate),
        ]);
    }

    public function create(array $extra = []): Activation
    {
        return parent::build($extra);
    }

    public function make(array $extra = []): Activation
    {
        return parent::build($extra, 'make');
    }

    public function getDefaults(Faker $faker): array
    {
        return [];
    }
}
