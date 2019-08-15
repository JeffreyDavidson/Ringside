<?php

namespace Tests\Feature\Generic\Stables;

use Carbon\Carbon;
use Tests\TestCase;
use App\Models\Stable;
use App\Models\TagTeam;
use App\Models\Wrestler;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * @group stables
 * @group generics
 */
class CreateStableSuccessConditionsTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Valid parameters for request.
     *
     * @param  array $overrides
     * @return array
     */
    private function validParams($overrides = [])
    {
        $wrestler = factory(Wrestler::class)->states('bookable')->create();
        $tagteam = factory(TagTeam::class)->states('bookable')->create();

        return array_replace_recursive([
            'name' => 'Example Stable Name',
            'started_at' => now()->toDateTimeString(),
            'wrestlers' => [$wrestler->getKey()],
            'tagteams' => [$tagteam->getKey()],
        ], $overrides);
    }

    /** @test */
    public function wrestlers_are_added_to_stable_if_present()
    {
        $now = now()->subDays(3);
        Carbon::setTestNow($now);

        $this->actAs('administrator');
        $createdWrestlers = factory(Wrestler::class, 3)->states('bookable')->create();

        $this->post(route('stables.store'), $this->validParams([
            'started_at' => $now->toDateTimeString(),
            'wrestlers' => $createdWrestlers->modelKeys()
        ]));

        tap(Stable::first()->currentWrestlers, function ($wrestlers) use ($createdWrestlers) {
            $this->assertCount(3, $wrestlers);
            $this->assertEquals($wrestlers->modelKeys(), $createdWrestlers->modelKeys());
        });
    }

    /** @test */
    public function tag_teams_are_added_to_stable_if_present()
    {
        $this->actAs('administrator');
        $createdTagTeams = factory(TagTeam::class, 3)->states('bookable')->create();

        $this->post(route('stables.store'), $this->validParams([
            'tagteams' => $createdTagTeams->modelKeys()
        ]));

        tap(Stable::first()->currentTagTeams, function ($tagteams) use ($createdTagTeams) {
            $this->assertCount(3, $tagteams);
            $this->assertEquals($tagteams->modelKeys(), $createdTagTeams->modelKeys());
        });
    }

    /** @test */
    public function a_stables_members_join_when_stable_is_started_if_filled()
    {
        $now = now()->subDays(3);
        Carbon::setTestNow($now);

        $this->actAs('administrator');

        $this->post(route('stables.store'), $this->validParams([
            'started_at' => $now->toDateTimeString()
        ]));

        tap(Stable::first(), function ($stable) use ($now) {
            $wrestlers = $stable->currentWrestlers()->get();
            $tagteams = $stable->currentTagTeams()->get();
            $wrestlers->each(function ($wrestler) use ($now) {
                $this->assertEquals(
                    $now->toDateTimeString(),
                    $wrestler->pivot->joined_at->toDateTimeString()
                );
            });
            $tagteams->each(function ($tagteam) use ($now) {
                $this->assertEquals(
                    $now->toDateTimeString(),
                    $tagteam->pivot->joined_at->toDateTimeString()
                );
            });
        });
    }

    /** @test */
    public function a_stables_members_join_at_the_current_time_when_stable_is_created_if_started_at_is_not_filled()
    {
        $now = now();
        Carbon::setTestNow($now);

        $this->actAs('administrator');

        $this->post(route('stables.store'), $this->validParams([
            'started_at' => ''
        ]));

        tap(Stable::first(), function ($stable) use ($now) {
            $wrestlers = $stable->currentWrestlers()->get();
            $tagteams = $stable->currentTagTeams()->get();
            $wrestlers->each(function ($wrestler) use ($now) {
                $this->assertEquals(
                    $now->toDateTimeString(),
                    $wrestler->pivot->joined_at->toDateTimeString()
                );
            });
            $tagteams->each(function ($tagteam) use ($now) {
                $this->assertEquals(
                    $now->toDateTimeString(),
                    $tagteam->pivot->joined_at->toDateTimeString()
                );
            });
        });
    }
}
