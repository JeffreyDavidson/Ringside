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
        $wrestlers = Wrestler::factory()->bookable()->times(1)->create();
        $tagTeam = TagTeam::factory()->bookable()->times(1)->create();

        return array_replace([
            'name' => 'Example Stable Name',
            'started_at' => now()->toDateTimeString(),
            'wrestlers' => [$wrestlers->pluck('id')],
            'tagteams' => [$tagTeam->pluck('id')],
        ], $overrides);
    }

    /**
     * @test
     * @dataProvider administrators
     */
    public function index_returns_a_view($administrators)
    {
        $this->actAs($administrators);

        $response = $this->indexRequest('stables');

        $response->assertOk();
        $response->assertViewIs('stables.index');
        $response->assertSeeLivewire('stables.active-stables');
        $response->assertSeeLivewire('stables.future-activation-and-unactivated-stables');
        $response->assertSeeLivewire('stables.inactive-stables');
        $response->assertSeeLivewire('stables.retired-stables');
    }

    /** @test */
    public function a_basic_user_cannot_view_stables_index_page()
    {
        $this->actAs(Role::BASIC);

        $this->indexRequest('stables')->assertForbidden();
    }

    /** @test */
    public function a_guest_cannot_view_stables_index_page()
    {
        $this->indexRequest('stables')->assertRedirect(route('login'));
    }

    /**
     * @test
     * @dataProvider administrators
     */
    public function create_returns_a_view($administrators)
    {
        $this->actAs($administrators);

        $response = $this->createRequest('stables');

        $response->assertViewIs('stables.create');
        $response->assertViewHas('stable', new Stable);
    }

    /**
     * @test
     * @dataProvider administrators
     */
    public function store_creates_a_stable_and_redirects($administrators)
    {
        $this->actAs($administrators);

        $response = $this->storeRequest('stables', $this->validParams());
        dd($response);

        $response->assertRedirect(route('stables.index'));
        tap(Stable::first(), function ($stable) {
            $this->assertEquals('Example Tag Team Name', $stable->name);
            $this->assertEquals('The Finisher', $stable->signature_move);
        });
    }

    /**
     * @test
     * @dataProvider administrators
     */
    public function an_employment_is_not_created_for_the_stable_if_started_at_is_filled_in_request($administrators)
    {
        $this->actAs($administrators);

        $this->storeRequest('stables', $this->validParams(['started_at' => null]));

        tap(Stable::first(), function ($stable) {
            $this->assertCount(0, $stable->employments);
        });
    }

    /**
     * @test
     * @dataProvider administrators
     */
    public function an_employment_is_created_for_the_stable_if_started_at_is_filled_in_request($administrators)
    {
        $startedAt = now()->toDateTimeString();

        $this->actAs($administrators);

        $this->storeRequest('stables', $this->validParams(['started_at' => $startedAt]));

        tap(Stable::first(), function ($stable) use ($startedAt) {
            $this->assertCount(1, $stable->employments);
            $this->assertEquals($startedAt, $stable->employments->first()->started_at->toDateTimeString());
        });
    }

    /** @test */
    public function wrestlers_are_added_to_stable_if_present()
    {
        $now = now()->subDays(3);
        Carbon::setTestNow($now);

        $this->actAs(Role::ADMINISTRATOR);
        $createdWrestlers = Wrestler::factory()->bookable()->times(3)->create();

        $this->post(route('stables.store'), $this->validParams([
            'started_at' => $now->toDateTimeString(),
            'wrestlers' => $createdWrestlers->modelKeys(),
        ]));

        tap(Stable::first()->currentWrestlers, function ($wrestlers) use ($createdWrestlers) {
            $this->assertCount(3, $wrestlers);
            $this->assertEquals($wrestlers->modelKeys(), $createdWrestlers->modelKeys());
        });
    }

    /** @test */
    public function tag_teams_are_added_to_stable_if_present()
    {
        $this->actAs(Role::ADMINISTRATOR);
        $createdTagTeams = TagTeam::factory()->bookable()->times(3)->create();

        $this->post(route('stables.store'), $this->validParams([
            'tagteams' => $createdTagTeams->modelKeys(),
        ]));

        tap(Stable::first()->currentTagTeams, function ($tagTeams) use ($createdTagTeams) {
            $this->assertCount(3, $tagTeams);
            $this->assertEquals($tagTeams->modelKeys(), $createdTagTeams->modelKeys());
        });
    }

    /** @test */
    public function a_stables_members_join_when_stable_is_started_if_filled()
    {
        $now = now()->subDays(3);
        Carbon::setTestNow($now);

        $this->actAs(Role::ADMINISTRATOR);

        $this->post(route('stables.store'), $this->validParams([
            'started_at' => $now->toDateTimeString(),
        ]));

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

    /** @test */
    public function a_stables_members_join_at_the_current_time_when_stable_is_created_if_started_at_is_not_filled()
    {
        $now = now();
        Carbon::setTestNow($now);

        $this->actAs(Role::ADMINISTRATOR);

        $this->post(route('stables.store'), $this->validParams([
            'started_at' => '',
        ]));

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

    /** @test */
    public function a_basic_user_cannot_view_the_form_for_creating_a_stable()
    {
        $this->actAs(Role::BASIC);

        $this->createRequest('stables')->assertForbidden();
    }

    /** @test */
    public function a_basic_user_cannot_create_a_stable()
    {
        $this->actAs(Role::BASIC);

        $this->storeRequest('stables', $this->validParams())->assertForbidden();
    }

    /** @test */
    public function a_guest_cannot_view_the_form_for_creating_a_stable()
    {
        $response = $this->createRequest('stables');

        $response->assertRedirect(route('login'));
    }

    /** @test */
    public function a_guest_cannot_create_a_stable()
    {
        $this->storeRequest('stables', $this->validParams())->assertRedirect(route('login'));
    }

    /** @test */
    public function store_validates_using_a_form_request()
    {
        $this->assertActionUsesFormRequest(
            StablesController::class,
            'store',
            StoreRequest::class
        );
    }

    /**
     * @test
     * @dataProvider administrators
     */
    public function show_returns_a_view($administrators)
    {
        $this->actAs($administrators);
        $stable = Stable::factory()->create();

        $response = $this->showRequest($stable);

        $response->assertViewIs('stables.show');
        $this->assertTrue($response->data('stable')->is($stable));
    }

    /** @test */
    public function a_basic_user_can_view_their_stable_profile()
    {
        $signedInUser = $this->actAs(Role::BASIC);
        $stable = Stable::factory()->create(['user_id' => $signedInUser->id]);

        $this->showRequest($stable)->assertOk();
    }

    /** @test */
    public function a_basic_user_cannot_view_another_users_stable_profile()
    {
        $this->actAs(Role::BASIC);
        $otherUser = User::factory()->create();
        $stable = Stable::factory()->create(['user_id' => $otherUser->id]);

        $this->showRequest($stable)->assertForbidden();
    }

    /** @test */
    public function a_guest_cannot_view_a_stable_profile()
    {
        $stable = Stable::factory()->create();

        $this->showRequest($stable)->assertRedirect(route('login'));
    }

    /**
     * @test
     * @dataProvider administrators
     */
    public function edit_returns_a_view($administrators)
    {
        $this->actAs($administrators);
        $stable = Stable::factory()->create();

        $response = $this->editRequest($stable);

        $response->assertViewIs('stables.edit');
        $this->assertTrue($response->data('stable')->is($stable));
    }

    /**
     * @test
     * @dataProvider administrators
     */
    public function updates_a_stable_and_redirects($administrators)
    {
        $this->actAs($administrators);
        $stable = Stable::factory()->create();

        $response = $this->updateRequest($stable, $this->validParams());

        $response->assertRedirect(route('stables.index'));
        tap($stable->fresh(), function ($stable) {
            $this->assertEquals('Example Tag Team Name', $stable->name);
            $this->assertEquals('The Finisher', $stable->signature_move);
        });
    }

    public function wrestlers_of_stable_are_synced_when_stable_is_updated()
    {
        $this->actAs(Role::ADMINISTRATOR);
        $stable = Stable::factory()->active()->create();
        $wrestlers = Wrestler::factory()->bookable()->times(2)->create();

        $response = $this->updateRequest($stable, $this->validParams([
            'wrestlers' => $wrestlers->modelKeys(),
        ]));

        $response->assertRedirect(route('stables.index'));

        tap($stable->currentWrestlers->fresh(), function ($stableWrestlers) use ($wrestlers) {
            $this->assertCount(2, $stableWrestlers);
            $this->assertEquals($stableWrestlers->modelKeys(), $wrestlers->modelKeys());
        });
    }

    /** @test */
    public function a_basic_user_cannot_view_the_form_for_editing_a_stable()
    {
        $this->actAs(Role::BASIC);
        $stable = Stable::factory()->create();

        $this->editRequest($stable)->assertForbidden();
    }

    /** @test */
    public function a_basic_user_cannot_update_a_stable()
    {
        $this->actAs(Role::BASIC);
        $stable = Stable::factory()->create();

        $this->updateRequest($stable, $this->validParams())->assertForbidden();
    }

    /** @test */
    public function a_guest_cannot_view_the_form_for_editing_a_stable()
    {
        $stable = Stable::factory()->create();

        $this->editRequest($stable)->assertRedirect(route('login'));
    }

    /** @test */
    public function a_guest_cannot_update_a_stable()
    {
        $stable = Stable::factory()->create();

        $this->updateRequest($stable, $this->validParams())->assertRedirect(route('login'));
    }

    /** @test */
    public function update_validates_using_a_form_request()
    {
        $this->assertActionUsesFormRequest(
            StablesController::class,
            'update',
            UpdateRequest::class
        );
    }

    /**
     * @test
     * @dataProvider administrators
     */
    public function deletes_a_stable_and_redirects($administrators)
    {
        $this->actAs($administrators);
        $stable = Stable::factory()->create();

        $response = $this->deleteRequest($stable);

        $response->assertRedirect(route('stables.index'));
        $this->assertSoftDeleted($stable);
    }

    /** @test */
    public function a_basic_user_cannot_delete_a_stable()
    {
        $this->actAs(Role::BASIC);
        $stable = Stable::factory()->create();

        $this->deleteRequest($stable)->assertForbidden();
    }

    /** @test */
    public function a_guest_cannot_delete_a_stable()
    {
        $stable = Stable::factory()->create();

        $this->deleteRequest($stable)->assertRedirect(route('login'));
    }
}
