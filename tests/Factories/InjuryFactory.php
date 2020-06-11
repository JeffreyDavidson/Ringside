<?php

namespace Tests\Factories;

use Carbon\Carbon;
use App\Models\Injury;
use App\Models\Manager;
use App\Models\Referee;
use App\Models\Wrestler;
use Illuminate\Support\Collection;

class InjuryFactory extends BaseFactory
{
    /** @var \Carbon\Carbon|null */
    public $startDate;
    /** @var \Carbon\Carbon|null */
    public $endDate;
    /** @var Wrestler[] */
    public $wrestlers;
    /** @var Manager[] */
    public $managers;
    /** @var Referee[] */
    public $referees;

    /**
     * @param string|Carbon $startDate
     */
    public function started($startDate = 'now')
    {
        $clone = clone $this;
        $clone->startDate = $startDate instanceof Carbon ? $startDate : new Carbon($startDate);

        return $clone;
    }

    /**
     * @param string|Carbon $endDate
     */
    public function ended($endDate = 'now')
    {
        $clone = clone $this;
        $clone->endDate = $endDate instanceof Carbon ? $endDate : new Carbon($endDate);

        return $clone;
    }

    public function forWrestler(Wrestler $wrestler)
    {
        return $this->forWrestlers([$wrestler]);
    }

    public function forWrestlers($wrestlers)
    {
        $clone = clone $this;
        $clone->wrestlers = $wrestlers;

        return $clone;
    }

    public function forManager(Manager $manager)
    {
        return $this->forManagers([$manager]);
    }

    public function forManagers($managers)
    {
        $clone = clone $this;
        $clone->managers = $managers;

        return $clone;
    }

    public function forReferee(Referee $referee)
    {
        return $this->forReferees([$referee]);
    }

    public function forReferees($referees)
    {
        $clone = clone $this;
        $clone->referees = $referees;

        return $clone;
    }

    public function create($attributes = [])
    {
        $injurables = collect()
            ->merge($this->wrestlers)
            ->merge($this->referees)
            ->merge($this->managers)
            ->flatten(1);


        $this->startDate = $this->startDate ?? now();

        if (empty($injurables)) {
            throw new \Exception('Attempted to create an injury without an injurable entity');
        }

        $injuries = new Collection();

        foreach ($injurables as $injuree) {
            $injury = new Injury();
            $injury->started_at = $this->startDate;
            $injury->ended_at = $this->endDate;
            $injury->injurable()->associate($injuree);
            $injury->save();
            $injuries->push($injuree);
        }

        return $injuries->count() === 1 ? $injuries->first() : $injuries;
    }
}
