<?php

namespace Tests\Feature\Http\Controllers\Stables;

use App\Enums\Role;
use App\Http\Controllers\Stables\StablesController;
use App\Http\Requests\Stables\StoreRequest;
use App\Http\Requests\Stables\UpdateRequest;
use App\Models\Stable;
use App\Models\TagTeam;
use App\Models\User;
use App\Models\Wrestler;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * @group stables
 * @group feature-stables
 * @group roster
 * @group feature-roster
 */
class StableControllerTest extends TestCase
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
        $wrestler = Wrestler::factory()->bookable()->create();
        $tagTeam = TagTeam::factory()->bookable()->create();

        return array_replace([
            'name' => 'Example Stable Name',
            'started_at' => now()->toDateTimeString(),
            'wrestlers' => $overrides['wrestlers'] ?? [$wrestler->id],
            'tag_teams' => $overrides['tag_teams'] ?? $tagTeam->pluck('id')->toArray(),
        ], $overrides);
    }

    /**
     * @test
     * @dataProvider administrators
     */
    public function index_returns_a_view($administrators)
    {
        $this->actAs($administrators)
            ->get(route('stables.index'))
            ->assertOk()
            ->assertViewIs('stables.index')
            ->assertSeeLivewire('stables.active-stables')
            ->assertSeeLivewire('stables.future-activation-and-unactivated-stables')
            ->assertSeeLivewire('stables.inactive-stables')
            ->assertSeeLivewire('stables.retired-stables');
    }

    /**
     * @test
     */
    public function a_basic_user_cannot_view_stables_index_page()
    {
        $this->actAs(Role::BASIC)
            ->get(route('stables.index'))
            ->assertForbidden();
    }

    /**
     * @test
     */
    public function a_guest_cannot_view_stables_index_page()
    {
        $this->get(route('stables.index'))
            ->assertRedirect(route('login'));
    }

    /**
     * @test
     * @dataProvider administrators
     */
    public function create_returns_a_view($administrators)
    {
        $this->actAs($administrators)
            ->get(route('stables.create'))
            ->assertViewIs('stables.create')
            ->assertViewHas('stable', new Stable);
    }

    /**
     * @test
     */
    public function a_basic_user_cannot_view_the_form_for_creating_a_stable()
    {
        $this->actAs(Role::BASIC)
            ->get(route('stables.create'))
            ->assertForbidden();
    }

    /**
     * @test
     */
    public function a_guest_cannot_view_the_form_for_creating_a_stable()
    {
        $this->get(route('stables.create'))
            ->assertRedirect(route('login'));
    }

    /**
     * @test
     * @dataProvider administrators
     */
    public function store_creates_a_stable_and_redirects($administrators)
    {
        $this->actAs($administrators)
            ->from(route('stables.create'))
            ->post(route('stables.store'), $this->validParams())
            ->assertRedirect(route('stables.index'));

        tap(Stable::first(), function ($stable) {
            $this->assertEquals('Example Stable Name', $stable->name);
        });
    }

    /**
     * @test
     * @dataProvider administrators
     */
    public function an_activation_is_not_created_for_the_stable_if_started_at_is_not_filled_in_request($administrators)
    {
        $this->actAs($administrators)
            ->from(route('stables.create'))
            ->post(route('stables.store'), $this->validParams(['started_at' => null]));

        tap(Stable::first(), function ($stable) {
            $this->assertCount(0, $stable->activations);
        });
    }

    /**
     * @test
     * @dataProvider administrators
     */
    public function an_activation_is_created_for_the_stable_if_started_at_is_filled_in_request($administrators)
    {
        $startedAt = now()->toDateTimeString();

        $this->actAs($administrators)
            ->from(route('stables.create'))
            ->post(route('stables.store'), $this->validParams(['started_at' => $startedAt]));

        tap(Stable::first(), function ($stable) use ($startedAt) {
            $this->assertCount(1, $stable->activations);
            $this->assertEquals($startedAt, $stable->activations->first()->started_at->toDateTimeString());
        });
    }

    /**
     * @test
     */
    public function wrestlers_are_added_to_stable_if_present()
    {
        $now = now()->subDays(3);
        Carbon::setTestNow($now);

        $createdWrestlers = Wrestler::factory()->count(3)->bookable()->create();

        $this->actAs(Role::ADMINISTRATOR)
            ->from('stables.create')
            ->post(route('stables.store'), $this->validParams([
                'started_at' => $now->toDateTimeString(),
                'wrestlers' => $createdWrestlers->modelKeys(),
            ]));

        tap(Stable::first()->currentWrestlers, function ($wrestlers) use ($createdWrestlers) {
            $this->assertCount(3, $wrestlers);
            $this->assertEquals($wrestlers->modelKeys(), $createdWrestlers->modelKeys());
        });
    }

    /**
     * @test
     */
    public function tag_teams_are_added_to_stable_if_present()
    {
        $tagTeam = TagTeam::factory()->bookable()->create();

        $this->actAs(Role::ADMINISTRATOR)
            ->from('stables.create')
            ->post(route('stables.store'), $this->validParams(['tag_teams' => [$tagTeam->getKey()]]));

        tap(Stable::first()->currentTagTeams, function ($tagTeams) use ($tagTeam) {
            $this->assertCount(1, $tagTeams);
            $this->assertTrue($tagTeams->contains($tagTeam));
        });
    }

    /**
     * @test
     */
    public function a_stables_members_join_when_stable_is_started_if_filled()
    {
        $now = now()->subDays(3);
        Carbon::setTestNow($now);

        $this->actAs(Role::ADMINISTRATOR)
            ->from('stables.create')
            ->post(route('stables.store'), $this->validParams(['started_at' => $now->toDateTimeString()]));

        tap(Stable::first(), function ($stable) use ($now) {
            $wrestlers = $stable->currentWrestlers()->get();
            $tagTeams = $stable->currentTagTeams()->get();
            $wrestlers->each(function ($wrestler) use ($now) {
                $this->assertEquals(
                    $now->toDateTimeString(),
                    $wrestler->pivot->joined_at->toDateTimeString()
                );
            });
            $tagTeams->each(function ($tagTeam) use ($now) {
                $this->assertEquals(
                    $now->toDateTimeString(),
                    $tagTeam->pivot->joined_at->toDateTimeString()
                );
            });
        });
    }

    /**
     * @test
     */
    public function a_stables_members_join_at_the_current_time_when_stable_is_created_if_started_at_is_not_filled()
    {
        $now = now();
        Carbon::setTestNow($now);

        $this->actAs(Role::ADMINISTRATOR)
            ->from('stables.create')
            ->post(route('stables.store'), $this->validParams(['started_at' => '']));

        tap(Stable::first(), function ($stable) use ($now) {
            $wrestlers = $stable->currentWrestlers()->get();
            $tagTeams = $stable->currentTagTeams()->get();
            $wrestlers->each(function ($wrestler) use ($now) {
                $this->assertEquals(
                    $now->toDateTimeString(),
                    $wrestler->pivot->joined_at->toDateTimeString()
                );
            });
            $tagTeams->each(function ($tagTeam) use ($now) {
                $this->assertEquals(
                    $now->toDateTimeString(),
                    $tagTeam->pivot->joined_at->toDateTimeString()
                );
            });
        });
    }

    /**
     * @test
     */
    public function a_basic_user_cannot_create_a_stable()
    {
        $this->actAs(Role::BASIC)
            ->from('stables.create')
            ->post(route('stables.store'), $this->validParams())
            ->assertForbidden();
    }

    /**
     * @test
     */
    public function a_guest_cannot_create_a_stable()
    {
        $this->from('stables.create')
            ->post(route('stables.store'), $this->validParams())
            ->assertRedirect(route('login'));
    }

    /**
     * @test
     */
    public function store_validates_using_a_form_request()
    {
        $this->assertActionUsesFormRequest(StablesController::class, 'store', StoreRequest::class);
    }

    /**
     * @test
     * @dataProvider administrators
     */
    public function show_returns_a_view($administrators)
    {
        $stable = Stable::factory()->create();

        $this->actAs($administrators)
            ->get(route('stables.show', $stable))
            ->assertViewIs('stables.show')
            ->assertViewHas('stable', $stable);
    }

    /**
     * @test
     */
    public function a_basic_user_can_view_their_stable_profile()
    {
        $signedInUser = $this->actAs(Role::BASIC);
        $stable = Stable::factory()->create(['user_id' => $signedInUser->id]);

        $this->get(route('stables.show', $stable))
            ->assertOk();
    }

    /**
     * @test
     */
    public function a_basic_user_cannot_view_another_users_stable_profile()
    {
        $otherUser = User::factory()->create();
        $stable = Stable::factory()->create(['user_id' => $otherUser->id]);

        $this->actAs(Role::BASIC)
            ->get(route('stables.show', $stable))
            ->assertForbidden();
    }

    /**
     * @test
     */
    public function a_guest_cannot_view_a_stable_profile()
    {
        $stable = Stable::factory()->create();

        $this->get(route('stables.show', $stable))
            ->assertRedirect(route('login'));
    }

    /**
     * @test
     * @dataProvider administrators
     */
    public function edit_returns_a_view($administrators)
    {
        $stable = Stable::factory()->create();

        $this->actAs($administrators)
            ->get(route('stables.edit', $stable))
            ->assertViewIs('stables.edit')
            ->assertViewHas('stable', $stable);
    }

    /**
     * @test
     */
    public function a_basic_user_cannot_view_the_form_for_editing_a_stable()
    {
        $stable = Stable::factory()->create();

        $this->actAs(Role::BASIC)
            ->get(route('stables.edit', $stable))
            ->assertForbidden();
    }

    /**
     * @test
     */
    public function a_guest_cannot_view_the_form_for_editing_a_stable()
    {
        $stable = Stable::factory()->create();

        $this->get(route('stables.edit', $stable))
            ->assertRedirect(route('login'));
    }

    /**
     * @test
     * @dataProvider administrators
     */
    public function updates_a_stable_and_redirects($administrators)
    {
        $stable = Stable::factory()->create();

        $this->actAs($administrators)
            ->from(route('stables.edit', $stable))
            ->put(route('stables.update', $stable), $this->validParams())
            ->assertRedirect(route('stables.index'));

        tap($stable->fresh(), function ($stable) {
            $this->assertEquals('Example Stable Name', $stable->name);
        });
    }

    public function wrestlers_of_stable_are_synced_when_stable_is_updated()
    {
        $stable = Stable::factory()->active()->create();
        $wrestlers = Wrestler::factory()->bookable()->times(2)->create();

        $this->actAs(Role::ADMINISTRATOR)
            ->from(route('stables.edit', $stable))
            ->put(route('stables.update', $stable), $this->validParams(['wrestlers' => $wrestlers->modelKeys()]))
            ->assertRedirect(route('stables.index'));

        tap($stable->currentWrestlers->fresh(), function ($stableWrestlers) use ($wrestlers) {
            $this->assertCount(2, $stableWrestlers);
            $this->assertEquals($stableWrestlers->modelKeys(), $wrestlers->modelKeys());
        });
    }

    /**
     * @test
     */
    public function a_basic_user_cannot_update_a_stable()
    {
        $stable = Stable::factory()->create();

        $this->actAs(Role::BASIC)
            ->from(route('stables.edit', $stable))
            ->put(route('stables.update', $stable), $this->validParams())
            ->assertForbidden();
    }

    /**
     * @test
     */
    public function a_guest_cannot_update_a_stable()
    {
        $stable = Stable::factory()->create();

        $this->from(route('stables.edit', $stable))
            ->put(route('stables.update', $stable), $this->validParams())
            ->assertRedirect(route('login'));
    }

    /**
     * @test
     */
    public function update_validates_using_a_form_request()
    {
        $this->assertActionUsesFormRequest(StablesController::class, 'update', UpdateRequest::class);
    }

    /**
     * @test
     * @dataProvider administrators
     */
    public function deletes_a_stable_and_redirects($administrators)
    {
        $stable = Stable::factory()->create();

        $this->actAs($administrators)
            ->delete(route('stables.destroy', $stable))
            ->assertRedirect(route('stables.index'));

        $this->assertSoftDeleted($stable);
    }

    /**
     * @test
     */
    public function a_basic_user_cannot_delete_a_stable()
    {
        $stable = Stable::factory()->create();

        $this->actAs(Role::BASIC)
            ->delete(route('stables.destroy', $stable))
            ->assertForbidden();
    }

    /**
     * @test
     */
    public function a_guest_cannot_delete_a_stable()
    {
        $stable = Stable::factory()->create();

        $this->delete(route('stables.destroy', $stable))
            ->assertRedirect(route('login'));
    }
}
