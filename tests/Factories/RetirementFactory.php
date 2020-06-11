<?php

namespace Tests\Factories;

use App\Models\Manager;
use App\Models\Referee;
use App\Models\Retirement;
use App\Models\Stable;
use App\Models\TagTeam;
use App\Models\Title;
use App\Models\Wrestler;
use Carbon\Carbon;
use Christophrumpel\LaravelFactoriesReloaded\BaseFactory;
use Faker\Generator as Faker;
use Illuminate\Support\Collection;

class RetirementFactory extends BaseFactory
{
    /** @var \Carbon\Carbon|null */
    public $startDate;

    /** @var \Carbon\Carbon|null */
    public $endDate;

    /** @var TagTeam[] */
    public $tagTeams;

    /** @var Wrestler[] */
    public $wrestlers;

    /** @var Manager[] */
    public $managers;

    /** @var Referee[] */
    public $referees;

    /** @var Stable[] */
    public $stables;

    /** @var Title[] */
    public $titles;

    public function getDefaults(Faker $faker): array
    {
        return [];
    }

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

    public function forTagTeam(TagTeam $tagTeam)
    {
        return $this->forTagTeams([$tagTeam]);
    }

    public function forTagTeams($tagTeams)
    {
        $clone = clone $this;
        $clone->tagTeams = $tagTeams;

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
        $clone->tagTeams = [];

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

    public function forStable(Stable $stable)
    {
        return $this->forStables([$stable]);
    }

    public function forStables($stables)
    {
        $clone = clone $this;
        $clone->stables = $stables;

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

    public function create($attributes = [])
    {
        $retirees = collect()
            ->merge($this->tagTeams)
            ->merge($this->wrestlers)
            ->merge($this->referees)
            ->merge($this->stables)
            ->merge($this->managers)
            ->merge($this->titles)
            ->flatten(1);

        $this->startDate = $this->startDate ?? now();

        if (empty($retirees)) {
            throw new \Exception('Attempted to create an retirement without a retireable entity');
        }

        $retirements = new Collection();

        foreach ($retirees as $retiree) {
            $retirement = new Retirement();
            $retirement->started_at = $this->startDate;
            $retirement->ended_at = $this->endDate;
            $retirement->retiree()->associate($retiree);
            $retirement->save();
            $retirements->push($retirement);
            if ($retiree instanceof TagTeam && $retiree->currentWrestlers->isNotEmpty()) {
                $this->forWrestlers($retiree->currentWrestlers)->create();
            }

            if ($retiree instanceof Stable && $retiree->currentWrestlers->isNotEmpty()) {
                $this->forWrestlers($retiree->currentWrestlers)->create();
            }

            if ($retiree instanceof Stable && $retiree->currentTagTeams->isNotEmpty()) {
                $this->forTagTeams($retiree->currentTagTeams)->create();
            }
        }

        return $retirements->count() === 1 ? $retirements->first() : $retirements;
    }


}
