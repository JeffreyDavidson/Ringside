<?php

namespace Tests\Feature\Http\Controllers\Stables;

use App\Enums\Role;
use App\Http\Controllers\Stables\StablesController;
use App\Models\Stable;
use App\Models\TagTeam;
use App\Models\Wrestler;
use Carbon\Carbon;
use Tests\Factories\StableRequestDataFactory;
use Tests\TestCase;

/**
 * @group stables
 * @group feature-stables
 * @group roster
 * @group feature-roster
 */
class StableControllerStoreMethodTest extends TestCase
{
    /**
     * @test
     */
    public function create_returns_a_view()
    {
        $this
            ->actAs(Role::administrator())
            ->get(action([StablesController::class, 'create']))
            ->assertViewIs('stables.create')
            ->assertViewHas('stable', new Stable);
    }

    /**
     * @test
     */
    public function a_basic_user_cannot_view_the_form_for_creating_a_stable()
    {
        $this
            ->actAs(Role::basic())
            ->get(action([StablesController::class, 'create']))
            ->assertForbidden();
    }

    /**
     * @test
     */
    public function a_guest_cannot_view_the_form_for_creating_a_stable()
    {
        $this
            ->get(action([StablesController::class, 'create']))
            ->assertRedirect(route('login'));
    }

    /**
     * @test
     */
    public function store_creates_a_stable_and_redirects()
    {
        $this
            ->actAs(Role::administrator())
            ->from(action([StablesController::class, 'create']))
            ->post(
                action([StablesController::class, 'store']),
                StableRequestDataFactory::new()->create([
                    'name' => 'Example Stable Name',
                ])
            )
            ->assertRedirect(action([StablesController::class, 'index']));

        tap(Stable::first(), function ($stable) {
            $this->assertEquals('Example Stable Name', $stable->name);
        });
    }

    /**
     * @test
     */
    public function an_activation_is_created_for_the_stable_if_started_at_is_filled_in_request()
    {
        $startedAt = now();
        $wrestlers = Wrestler::factory()->count(3)->create();

        $this
            ->actAs(Role::administrator())
            ->from(action([StablesController::class, 'create']))
            ->post(
                action([StablesController::class, 'store']),
                StableRequestDataFactory::new()
                    ->withStartDate($startedAt)
                    ->withWrestlers($wrestlers->modelKeys())
                    ->create()
            );

        tap(Stable::first(), function ($stable) use ($startedAt) {
            $this->assertCount(1, $stable->activations);
            $this->assertEquals($startedAt, $stable->activatedAt->toDateTimeString());
        });
    }

    /**
     * @test
     */
    public function wrestlers_are_added_to_stable_if_present()
    {
        $createdWrestlers = Wrestler::factory()->count(3)->create()->pluck('id')->toArray();

        $this
            ->actAs(Role::administrator())
            ->from(action([StablesController::class, 'create']))
            ->post(
                action([StablesController::class, 'store']),
                StableRequestDataFactory::new()->withWrestlers($createdWrestlers)->create()
            );

        tap(Stable::first()->currentWrestlers, function ($wrestlers) use ($createdWrestlers) {
            $this->assertCount(3, $wrestlers);
            $this->assertEquals($wrestlers->modelKeys(), $createdWrestlers);
        });
    }

    /**
     * @test
     */
    public function tag_teams_are_added_to_stable_if_present()
    {
        $createdTagTeams = TagTeam::factory()->count(2)->create()->pluck('id')->toArray();

        $this
            ->actAs(Role::administrator())
            ->from(action([StablesController::class, 'create']))
            ->post(
                action([StablesController::class, 'store']),
                StableRequestDataFactory::new()->withTagTeams($createdTagTeams)->create()
            );

        tap(Stable::first()->currentTagTeams, function ($tagTeams) use ($createdTagTeams) {
            $this->assertCount(2, $tagTeams);
            $this->assertEquals($tagTeams->modelKeys(), $createdTagTeams);
        });
    }

    /**
     * @test
     */
    public function a_stables_members_join_when_stable_is_started_if_filled()
    {
        $createdWrestlers = Wrestler::factory()->count(3)->create();

        $this
            ->actAs(Role::administrator())
            ->from(action([StablesController::class, 'create']))
            ->post(
                action([StablesController::class, 'store']),
                StableRequestDataFactory::new()
                    ->withStartDate(Carbon::now())
                    ->withWrestlers($createdWrestlers->modelKeys())
                    ->create()
            );

        tap(Stable::first(), function ($stable) {
            $wrestlers = $stable->currentWrestlers;
            foreach ($wrestlers as $wrestler) {
                $this->assertNotNull($wrestler->pivot->joined_at);
            }
        });
    }

    /**
     * @test
     */
    public function a_stables_members_join_at_the_current_time_when_stable_is_created_if_started_at_is_not_filled()
    {
        $wrestler = Wrestler::factory()->create()->getKey();
        $tagTeam = TagTeam::factory()->create()->getKey();
        $now = now()->toDateTimeString();
        Carbon::setTestNow($now);

        $this
            ->actAs(Role::administrator())
            ->from(action([StablesController::class, 'create']))
            ->post(
                action([StablesController::class, 'store']),
                StableRequestDataFactory::new()->withWrestlers([$wrestler])->withTagTeams([$tagTeam])->create([
                    'started_at' => '',
                ])
            );

        tap(Stable::first(), function ($stable) use ($wrestler, $tagTeam, $now) {
            $wrestlers = $stable->currentWrestlers;
            $tagTeams = $stable->currentTagTeams;

            $this->assertCollectionHas($wrestlers, $wrestler);
            $this->assertCollectionHas($tagTeams, $tagTeam);

            $this->assertEquals($now, $wrestlers->first()->pivot->joined_at->toDateTimeString());
            $this->assertEquals($now, $tagTeams->first()->pivot->joined_at->toDateTimeString());
        });
    }

    /**
     * @test
     */
    public function a_basic_user_cannot_create_a_stable()
    {
        $this
            ->actAs(Role::basic())
            ->from(action([StablesController::class, 'create']))
            ->post(action([StablesController::class, 'store']), StableRequestDataFactory::new()->create())
            ->assertForbidden();
    }

    /**
     * @test
     */
    public function a_guest_cannot_create_a_stable()
    {
        $this
            ->from(action([StablesController::class, 'create']))
            ->post(action([StablesController::class, 'store']), StableRequestDataFactory::new()->create())
            ->assertRedirect(route('login'));
    }
}
