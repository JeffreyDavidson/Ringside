<?php

namespace Tests\Feature\Http\Controllers\TagTeams;

use App\Enums\Role;
use App\Http\Controllers\TagTeams\TagTeamsController;
use App\Http\Requests\TagTeams\StoreRequest;
use App\Http\Requests\TagTeams\UpdateRequest;
use App\Models\TagTeam;
use App\Models\User;
use App\Models\Wrestler;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * @group tagteams
 * @group feature-tagteams
 * @group roster
 * @group feature-roster
 */
class TagTeamControllerTest extends TestCase
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
        $wrestlers = Wrestler::factory()->bookable()->count(2)->create();

        return array_replace_recursive([
            'name' => 'Example Tag Team Name',
            'signature_move' => 'The Finisher',
            'started_at' => now()->toDateTimeString(),
            'wrestlers' => $overrides['wrestlers'] ?? $wrestlers->pluck('id')->toArray(),
        ], $overrides);
    }

    /**
     * @test
     * @dataProvider administrators
     */
    public function index_returns_a_view($administrators)
    {
        $this->actAs($administrators);

        $response = $this->get(route('tag-teams.index'));

        $response->assertOk();
        $response->assertViewIs('tagteams.index');
        $response->assertSeeLivewire('tag-teams.employed-tag-teams');
        $response->assertSeeLivewire('tag-teams.future-employed-and-unemployed-tag-teams');
        $response->assertSeeLivewire('tag-teams.released-tag-teams');
        $response->assertSeeLivewire('tag-teams.suspended-tag-teams');
        $response->assertSeeLivewire('tag-teams.retired-tag-teams');
    }

    /** @test */
    public function a_basic_user_cannot_view_tag_teams_index_page()
    {
        $this->actAs(Role::BASIC);

        $this->get(route('tag-teams.index'))->assertForbidden();
    }

    /** @test */
    public function a_guest_cannot_view_tag_teams_index_page()
    {
        $this->get(route('tag-teams.index'))->assertRedirect(route('login'));
    }

    /**
     * @test
     * @dataProvider administrators
     */
    public function create_returns_a_view($administrators)
    {
        $this->actAs($administrators);

        $response = $this->get(route('tag-teams.create'));

        $response->assertViewIs('tagteams.create');
        $response->assertViewHas('tagTeam', new TagTeam);
    }

    /** @test */
    public function a_basic_user_cannot_view_the_form_for_creating_a_tag_team()
    {
        $this->actAs(Role::BASIC);

        $this->get(route('tag-teams.create'))->assertForbidden();
    }

    /** @test */
    public function a_guest_cannot_view_the_form_for_creating_a_tag_team()
    {
        $this->get(route('tag-teams.create'))->assertRedirect(route('login'));
    }

    /**
     * @test
     * @dataProvider administrators
     */
    public function store_creates_a_tag_team_and_redirects($administrators)
    {
        $this->actAs($administrators);

        $response = $this->from(route('tag-teams.create'))->post(route('tag-teams.store'), $this->validParams());

        $response->assertRedirect(route('tag-teams.index'));
        tap(TagTeam::first(), function ($tagTeam) {
            $this->assertEquals('Example Tag Team Name', $tagTeam->name);
            $this->assertEquals('The Finisher', $tagTeam->signature_move);
        });
    }

    /**
     * @test
     * @dataProvider administrators
     */
    public function an_employment_is_not_created_for_the_tag_team_if_started_at_is_filled_in_request($administrators)
    {
        $this->actAs($administrators);

        $this->from(route('tag-teams.create'))->post(route('tag-teams.store'), $this->validParams(['started_at' => null]));

        tap(TagTeam::first(), function ($tagTeam) {
            $this->assertCount(0, $tagTeam->employments);
        });
    }

    /**
     * @test
     * @dataProvider administrators
     */
    public function an_employment_is_created_for_the_tag_team_if_started_at_is_filled_in_request($administrators)
    {
        $startedAt = now()->toDateTimeString();

        $this->actAs($administrators);

        $response = $this->from(route('tag-teams.create'))->post(route('tag-teams.store'), $this->validParams(['started_at' => $startedAt]));

        tap(TagTeam::first(), function ($tagTeam) use ($startedAt) {
            $this->assertCount(1, $tagTeam->employments);
            $this->assertEquals($startedAt, $tagTeam->employments->first()->started_at->toDateTimeString());
        });
    }

    /** @test */
    public function a_basic_user_cannot_create_a_tag_team()
    {
        $this->actAs(Role::BASIC);

        $this->from(route('tag-teams.create'))->post(route('tag-teams.store'), $this->validParams())->assertForbidden();
    }

    /** @test */
    public function a_guest_cannot_create_a_tag_team()
    {
        $this->from(route('tag-teams.create'))->post(route('tag-teams.store'), $this->validParams())->assertRedirect(route('login'));
    }

    /** @test */
    public function store_validates_using_a_form_request()
    {
        $this->assertActionUsesFormRequest(TagTeamsController::class, 'store', StoreRequest::class);
    }

    /**
     * @test
     * @dataProvider administrators
     */
    public function show_returns_a_view($administrators)
    {
        $this->actAs($administrators);
        $tagTeam = TagTeam::factory()->create();

        $response = $this->get(route('tag-teams.show', $tagTeam));

        $response->assertViewIs('tagteams.show');
        $this->assertTrue($response->data('tagTeam')->is($tagTeam));
    }

    /** @test */
    public function a_basic_user_can_view_their_tag_team_profile()
    {
        $signedInUser = $this->actAs(Role::BASIC);
        $tagTeam = TagTeam::factory()->create(['user_id' => $signedInUser->id]);

        $this->get(route('tag-teams.show', $tagTeam))->assertOk();
    }

    /** @test */
    public function a_basic_user_cannot_view_another_users_tag_team_profile()
    {
        $this->actAs(Role::BASIC);
        $otherUser = User::factory()->create();
        $tagTeam = TagTeam::factory()->create(['user_id' => $otherUser->id]);

        $this->get(route('tag-teams.show', $tagTeam))->assertForbidden();
    }

    /** @test */
    public function a_guest_cannot_view_a_tag_team_profile()
    {
        $tagTeam = TagTeam::factory()->create();

        $this->get(route('tag-teams.show', $tagTeam))->assertRedirect(route('login'));
    }

    /**
     * @test
     * @dataProvider administrators
     */
    public function edit_returns_a_view($administrators)
    {
        $this->actAs($administrators);
        $tagTeam = TagTeam::factory()->create();

        $response = $this->get(route('tag-teams.edit', $tagTeam));

        $response->assertViewIs('tagteams.edit');
        $this->assertTrue($response->data('tagTeam')->is($tagTeam));
    }

    /** @test */
    public function a_basic_user_cannot_view_the_form_for_editing_a_tag_team()
    {
        $this->actAs(Role::BASIC);
        $tagTeam = TagTeam::factory()->create();

        $this->get(route('tag-teams', $tagTeam))->assertForbidden();
    }

    /** @test */
    public function a_guest_cannot_view_the_form_for_editing_a_tag_team()
    {
        $tagTeam = TagTeam::factory()->create();

        $this->get(route('tag-teams', $tagTeam))->assertRedirect(route('login'));
    }

    /**
     * @test
     * @dataProvider administrators
     */
    public function updates_a_tag_team_and_redirects($administrators)
    {
        $this->actAs($administrators);
        $tagTeam = TagTeam::factory()->create();

        $response = $this->from(route('tag-teams.edit', $tagTeam))->put(route('tag-teams.update', $tagTeam), $this->validParams());

        $response->assertRedirect(route('tag-teams.index'));
        tap($tagTeam->fresh(), function ($tagTeam) {
            $this->assertEquals('Example Tag Team Name', $tagTeam->name);
            $this->assertEquals('The Finisher', $tagTeam->signature_move);
        });
    }

    /**
     * @test
     * @dataProvider administrators
     */
    public function wrestlers_of_tag_team_are_synced_when_tag_team_is_updated()
    {
        $this->actAs(Role::ADMINISTRATOR);
        $tagTeam = TagTeam::factory()->bookable()->create();
        $formerTagTeamPartners = $tagTeam->currentWrestlers;

        $newTagTeamPartners = Wrestler::factory()->count(2)->bookable()->create();

        $this->assertCount(4, Wrestler::all());

        $response = $this->from(route('tag-teams.edit', $tagTeam))->put(route('tag-teams.update', $tagTeam), $this->validParams([
            'wrestlers' => $newTagTeamPartners->pluck('id')->toArray(),
        ]));

        $response->assertRedirect(route('tag-teams.index'));
        tap($tagTeam->fresh(), function ($tagTeam) use ($formerTagTeamPartners, $newTagTeamPartners) {
            $this->assertCount(4, $tagTeam->wrestlers);
            $this->assertCount(2, $tagTeam->currentWrestlers);
            $this->assertCollectionHas($tagTeam->currentWrestlers, $newTagTeamPartners[0]);
            $this->assertCollectionHas($tagTeam->currentWrestlers, $newTagTeamPartners[1]);
            $this->assertCollectionDoesntHave($tagTeam->currentWrestlers, $formerTagTeamPartners[0]);
            $this->assertCollectionDoesntHave($tagTeam->currentWrestlers, $formerTagTeamPartners[1]);
        });
    }

    /** @test */
    public function a_basic_user_cannot_update_a_tag_team()
    {
        $this->actAs(Role::BASIC);
        $tagTeam = TagTeam::factory()->create();

        $this->from(route('tag-teams.edit', $tagTeam))->put(route('tag-teams.update', $tagTeam), $this->validParams())->assertForbidden();
    }

    /** @test */
    public function a_guest_cannot_update_a_tag_team()
    {
        $tagTeam = TagTeam::factory()->create();

        $this->from(route('tag-teams.edit', $tagTeam))->put(route('tag-teams.update', $tagTeam), $this->validParams())->assertRedirect(route('login'));
    }

    /** @test */
    public function update_validates_using_a_form_request()
    {
        $this->assertActionUsesFormRequest(TagTeamsController::class, 'update', UpdateRequest::class);
    }

    /**
     * @test
     * @dataProvider administrators
     */
    public function deletes_a_tag_team_and_redirects($administrators)
    {
        $this->actAs($administrators);
        $tagTeam = TagTeam::factory()->create();

        $response = $this->delete(route('tag-teams.destroy', $tagTeam));

        $response->assertRedirect(route('tag-teams.index'));
        $this->assertSoftDeleted($tagTeam);
    }

    /** @test */
    public function a_basic_user_cannot_delete_a_tag_team()
    {
        $this->actAs(Role::BASIC);
        $tagTeam = TagTeam::factory()->create();

        $this->delete(route('tag-teams.destroy', $tagTeam))->assertForbidden();
    }

    /** @test */
    public function a_guest_cannot_delete_a_tag_team()
    {
        $tagTeam = TagTeam::factory()->create();

        $this->delete(route('tag-teams.destroy', $tagTeam))->assertRedirect(route('login'));
    }
}
