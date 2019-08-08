<?php

namespace Tests\Unit\Eloquent\Relationships;

use Tests\TestCase;
use App\Models\Stable;
use App\Models\Wrestler;
use Illuminate\Foundation\Testing\RefreshDatabase;

class LeaveableMorphToManyTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function wrestlers_that_are_attached_to_a_stable_are_in_the_stable()
    {
        $wrestlersToAddToStable = factory(Wrestler::class, 3)->states('bookable')->create();
        $stable = factory(Stable::class)->create();

        $stable->wrestlers()->attach($wrestlersToAddToStable->modelKeys());

        $this->assertCount(3, $stable->wrestlers);
        $this->assertCount(3, $stable->currentWrestlers);
    }

    /** @test */
    public function new_wrestlers_added_to_a_stable_are_in_the_stable()
    {
        $wrestlersToAddToStable = factory(Wrestler::class, 3)->states('bookable')->create();
        $stable = factory(Stable::class)->create();
        $stable->wrestlers()->attach($wrestlersToAddToStable->modelKeys());

        $onlyStableWrestler = factory(Wrestler::class)->states('bookable')->create();

        $stable->wrestlers()->sync($onlyStableWrestler->getKey());

        $this->assertCount(4, $stable->wrestlers);
        $this->assertCount(1, $stable->currentWrestlers);
    }

    /** @test */
    public function new_wrestlers_added_to_a_stable_are_only_wrestlers_in_the_stable()
    {
        $wrestlers = factory(Wrestler::class, 3)->states('bookable')->create();
        $stable = factory(Stable::class)->create();
        $stable->wrestlers()->attach($wrestlers->modelKeys());

        $newStableWrestler = factory(Wrestler::class)->states('bookable')->create(['name' => 'Kid Wonder']);
        $wrestlers->push($newStableWrestler);

        $stable->wrestlers()->sync($wrestlers->modelKeys());

        tap($stable->fresh(), function ($stable) {
            $this->assertCount(4, $stable->wrestlers);
            $this->assertCount(4, $stable->currentWrestlers);
        });
    }

    /** @test */
    public function a_wrestler_removed_from_a_stable_is_not_current()
    {
        $wrestlers = factory(Wrestler::class, 3)->states('bookable')->create();
        $stable = factory(Stable::class)->create();
        $stable->wrestlers()->attach($wrestlers->modelKeys());

        $stable->wrestlers()->detach($wrestlers->first()->getKey());

        tap($stable->fresh(), function ($stable) {
            $this->assertCount(3, $stable->wrestlers);
            $this->assertCount(2, $stable->currentWrestlers);
        });
    }
}
