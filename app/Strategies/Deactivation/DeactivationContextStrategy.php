<?php

namespace App\Strategies\Deactivation;

use App\Models\Stable;
use App\Models\Title;
use App\Repositories\DeactivationRepositoryInterface;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

class DeactivationContextStrategy
{
    /**
     * The strategy to be used for the given model.
     *
     * @var \App\Strategies\Deactivation\DeactivationRepositoryInterface
     */
    private DeactivationRepositoryInterface $strategy;

    /**
     * Create a new deactivation context strategy instance.
     *
     * @param  \Illuminate\Database\Eloquent\Model $model
     */
    public function __construct(Model $model)
    {
        if ($model instanceof Stable) {
            $this->strategy = new StableDeactivationStrategy($model);
        } elseif ($model instanceof Title) {
            $this->strategy = new TitleDeactivationStrategy($model);
        }

        throw new \InvalidArgumentException('Could not find strategy for: '.$model::class);
    }

    /**
     * Process the deactivation of the model.
     *
     * @param  \Carbon\Carbon|null $startedAt
     * @return void
     */
    public function process(Carbon $startedAt = null)
    {
        $this->strategy->deactivate($startedAt);
    }
}
