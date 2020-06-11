<?php

namespace Tests\Factories;

use App\Models\Activation;
use App\Models\Title;
use Carbon\Carbon;
use Christophrumpel\LaravelFactoriesReloaded\BaseFactory;
use Faker\Generator as Faker;
use Illuminate\Support\Collection;

class ActivationFactory extends BaseFactory
{
    /** @var \Carbon\Carbon|null */
    public $startDate;

    /** @var \Carbon\Carbon|null */
    public $endDate;

    /** @var Stable[] */
    public $stables;

    /** @var Title[] */
    public $titles;

    protected string $modelClass = Activation::class;

    public function create(array $extra = []): Activation
    {
        $activators = collect()
            ->merge($this->stables)
            ->merge($this->titles)
            ->flatten(1);

        $this->startDate = $this->startDate ?? now();

        if (empty($activators)) {
            throw new \Exception('Attempted to create an activation without a employable entity');
        }

        $activations = new Collection();

        foreach ($activators as $activator) {
            $activation = new Activation();
            $activation->started_at = $this->startDate;
            $activation->ended_at = $this->endDate;
            $activation->activatable()->associate($activator);
            $activation->save();
            $activations->push($activation);
            if ($activator instanceof Stable && $activator->currentWrestlers->isNotEmpty()) {
                // $this->forWrestlers($activation->currentWrestlers)->create();
                // Stable has wrestlers involved so attach a joined at to the stable.
            }
        }

        return $activations->count() === 1 ? $activations->first() : $activations;
    }

    public function make(array $extra = []): Activation
    {
        return parent::build($extra, 'make');
    }

    public function getDefaults(Faker $faker): array
    {
        return [];
    }

    public function started($startDate = 'now')
    {
        $clone = clone $this;

        $clone->startDate = $startDate instanceof Carbon ? $startDate : new Carbon($startDate);

        return $clone;
    }

    public function ended($endDate = 'now')
    {
        $clone = clone $this;

        $clone->endDate = $endDate instanceof Carbon ? $endDate : new Carbon($endDate);

        return $clone;
    }

    public function forTitle(Title $title)
    {
        return $this->forTitles([$title]);
    }

    public function forTitles($titles)
    {
        $clone = clone $this;
        $clone->titles = $titles;

        return $clone;
    }
}
