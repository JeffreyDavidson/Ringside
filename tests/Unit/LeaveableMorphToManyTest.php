<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\Stable;
use App\Models\Wrestler;
use Illuminate\Foundation\Testing\RefreshDatabase;

class LeaveableMorphToManyTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function a_newly_attached_model_is_in_current_and_history()
    {
        $wrestler = factory(Wrestler::class)->states('bookable')->create();
        $stable = factory(Stable::class)->create();

        $stable->wrestlerHistory()->attach($wrestler->getKey());

        $this->assertCount(1, $stable->wrestlerHistory);
        $this->assertCount(1, $stable->currentWrestlers);
    }

    /** @test */
    public function a_detached_model_is_in_history_only()
    {
        $wrestler = factory(Wrestler::class)->states('bookable')->create();
        $stable = factory(Stable::class)->create();

        $stable->wrestlerHistory()->attach($wrestler->getKey());
        $stable->wrestlerHistory()->detach($wrestler->getKey());

        $this->assertCount(1, $stable->wrestlerHistory);
        $this->assertCount(0, $stable->currentWrestlers);
    }

    /** @test */
    public function a_reattached_model_is_in_current_and_history()
    {
        $wrestler = factory(Wrestler::class)->states('bookable')->create();
        $stable = factory(Stable::class)->create();

        $stable->wrestlerHistory()->attach($wrestler->getKey());
        $stable->wrestlerHistory()->detach($wrestler->getKey());
        $stable->wrestlerHistory()->attach($wrestler->getKey());

        $this->assertCount(2, $stable->wrestlerHistory);
        $this->assertCount(1, $stable->currentWrestlers);
    }

    /** @test */
    public function attaching_a_new_model_does_not_affect_other_models()
    {
        $stable = factory(Stable::class)->create();
        $oldWrestler = factory(Wrestler::class)->states('bookable')->create();
        $stable->wrestlerHistory()->attach($oldWrestler->getKey());

        $newWrestler = factory(Wrestler::class)->states('bookable')->create();
        $stable->wrestlerHistory()->attach($newWrestler->getKey());

        $this->assertCount(2, $stable->wrestlerHistory);
        $this->assertCount(2, $stable->currentWrestlers);
    }

    /** @test */
    public function detaching_a_model_does_not_affect_other_models()
    {
        $stable = factory(Stable::class)->create();
        $wrestlerA = factory(Wrestler::class)->states('bookable')->create();
        $wrestlerB = factory(Wrestler::class)->states('bookable')->create();
        $stable->wrestlerHistory()->attach($wrestlerA->getKey());
        $stable->wrestlerHistory()->attach($wrestlerB->getKey());

        $stable->wrestlerHistory()->detach($wrestlerA->getKey());

        $this->assertCount(2, $stable->wrestlerHistory);
        $this->assertCount(1, $stable->currentWrestlers);
    }

    /** @test */
    public function syncing_the_relationship_attaches_new_models()
    {
        $stable = factory(Stable::class)->create();
        $newWrestler = factory(Wrestler::class)->states('bookable')->create();

        $stable->wrestlerHistory()->sync([$newWrestler->id]);

        $this->assertCount(1, $stable->wrestlerHistory);
        $this->assertCount(1, $stable->currentWrestlers);
    }

    /** @test */
    public function syncing_the_relationship_detaches_missing_models()
    {
        $wrestler = factory(Wrestler::class)->states('bookable')->create();
        $stable = factory(Stable::class)->create();
        $stable->wrestlerHistory()->attach($wrestler->getKey());
        $onlyStableWrestler = factory(Wrestler::class)->states('bookable')->create();

        $stable->wrestlerHistory()->sync($onlyStableWrestler->getKey());

        $this->assertCount(2, $stable->wrestlerHistory);
        $this->assertCount(1, $stable->currentWrestlers);
    }

}
