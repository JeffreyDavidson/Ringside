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
        dd($wrestlers);

        return array_replace_recursive([
            'name' => 'Example Tag Team Name',
            'signature_move' => 'The Finisher',
            'started_at' => now()->toDateTimeString(),
            'wrestler1' => $overrides['wrestlers'][0] ?? $wrestlers->first()->id,
            'wrestler2' => $overrides['wrestlers'][1] ?? $wrestlers->last()->id,
        ], $overrides);
    }

    /**
     * @test
     * @dataProvider administrators
     */
    public function index_returns_a_view($administrators)
    {
        $this->actAs($administrators);

        $response = $this->indexRequest('tag-teams');

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

        $this->indexRequest('tag-teams')->assertForbidden();
    }

    /** @test */
    public function a_guest_cannot_view_tag_teams_index_page()
    {
        $this->indexRequest('tag-teams')->assertRedirect(route('login'));
    }

    /**
     * @test
     * @dataProvider administrators
     */
    public function create_returns_a_view($administrators)
    {
        $this->actAs($administrators);

        $response = $this->createRequest('tag-teams');

        $response->assertViewIs('tagteams.create');
        $response->assertViewHas('tagTeam', new TagTeam);
    }

    /**
     * @test
     * @dataProvider administrators
     */
    public function store_creates_a_tag_team_and_redirects($administrators)
    {
        $this->actAs($administrators);

        $response = $this->storeRequest('tag-teams', $this->validParams());
        dd($response);

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

        $this->storeRequest('tag-teams', $this->validParams(['started_at' => null]));

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

        $response = $this->storeRequest('tag-teams', $this->validParams(['started_at' => $startedAt]));

        tap(TagTeam::first(), function ($tagTeam) use ($startedAt) {
            $this->assertCount(1, $tagTeam->employments);
            $this->assertEquals($startedAt, $tagTeam->employments->first()->started_at->toDateTimeString());
        });
    }

    /** @test */
    public function a_basic_user_cannot_view_the_form_for_creating_a_tag_team()
    {
        $this->actAs(Role::BASIC);

        $this->createRequest('tag-teams')->assertForbidden();
    }

    /** @test */
    public function a_basic_user_cannot_create_a_tag_team()
    {
        $this->actAs(Role::BASIC);

        $this->storeRequest('tag-teams', $this->validParams())->assertForbidden();
    }

    /** @test */
    public function a_guest_cannot_view_the_form_for_creating_a_tag_team()
    {
        $response = $this->createRequest('tag-teams');

        $response->assertRedirect(route('login'));
    }

    /** @test */
    public function a_guest_cannot_create_a_tag_team()
    {
        $this->storeRequest('tag-teams', $this->validParams())->assertRedirect(route('login'));
    }

    /** @test */
    public function store_validates_using_a_form_request()
    {
        $this->assertActionUsesFormRequest(
            TagTeamsController::class,
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
        $tagTeam = TagTeam::factory()->create();

        $response = $this->showRequest($tagTeam);

        $response->assertViewIs('tagteams.show');
        $this->assertTrue($response->data('tagTeam')->is($tagTeam));
    }

    /** @test */
    public function a_basic_user_can_view_their_tag_team_profile()
    {
        $signedInUser = $this->actAs(Role::BASIC);
        $tagTeam = TagTeam::factory()->create(['user_id' => $signedInUser->id]);

        $this->showRequest($tagTeam)->assertOk();
    }

    /** @test */
    public function a_basic_user_cannot_view_another_users_tag_team_profile()
    {
        $this->actAs(Role::BASIC);
        $otherUser = User::factory()->create();
        $tagTeam = TagTeam::factory()->create(['user_id' => $otherUser->id]);

        $this->showRequest($tagTeam)->assertForbidden();
    }

    /** @test */
    public function a_guest_cannot_view_a_tag_team_profile()
    {
        $tagTeam = TagTeam::factory()->create();

        $this->showRequest($tagTeam)->assertRedirect(route('login'));
    }

    /**
     * @test
     * @dataProvider administrators
     */
    public function edit_returns_a_view($administrators)
    {
        $this->actAs($administrators);
        $tagTeam = TagTeam::factory()->create();

        $response = $this->editRequest($tagTeam);

        $response->assertViewIs('tagteams.edit');
        $this->assertTrue($response->data('tagTeam')->is($tagTeam));
    }

    /**
     * @test
     * @dataProvider administrators
     */
    public function updates_a_tag_team_and_redirects($administrators)
    {
        $this->actAs($administrators);
        $tagTeam = TagTeam::factory()->create();

        $response = $this->updateRequest($tagTeam, $this->validParams());

        $response->assertRedirect(route('tag-teams.index'));
        tap($tagTeam->fresh(), function ($tagTeam) {
            $this->assertEquals('Example Tag Team Name', $tagTeam->name);
            $this->assertEquals('The Finisher', $tagTeam->signature_move);
        });
    }

    public function wrestlers_of_tag_team_are_synced_when_tag_team_is_updated()
    {
        $this->actAs(Role::ADMINISTRATOR);
        $tagTeam = TagTeam::factory()->bookable()->create();
        $wrestlers = Wrestler::factory()->count(2)->bookable()->create();

        $response = $this->updateRequest($tagTeam, $this->validParams([
            'wrestlers' => $wrestlers->modelKeys(),
        ]));

        $response->assertRedirect(route('tag-teams.index'));

        tap($tagTeam->currentWrestlers->fresh(), function ($tagTeamWrestlers) use ($wrestlers) {
            $this->assertCount(2, $tagTeamWrestlers);
            $this->assertEquals($tagTeamWrestlers->modelKeys(), $wrestlers->modelKeys());
        });
    }

    /** @test */
    public function a_basic_user_cannot_view_the_form_for_editing_a_tag_team()
    {
        $this->actAs(Role::BASIC);
        $tagTeam = TagTeam::factory()->create();

        $this->editRequest($tagTeam)->assertForbidden();
    }

    /** @test */
    public function a_basic_user_cannot_update_a_tag_team()
    {
        $this->actAs(Role::BASIC);
        $tagTeam = TagTeam::factory()->create();

        $this->updateRequest($tagTeam, $this->validParams())->assertForbidden();
    }

    /** @test */
    public function a_guest_cannot_view_the_form_for_editing_a_tag_team()
    {
        $tagTeam = TagTeam::factory()->create();

        $this->editRequest($tagTeam)->assertRedirect(route('login'));
    }

    /** @test */
    public function a_guest_cannot_update_a_tag_team()
    {
        $tagTeam = TagTeam::factory()->create();

        $this->updateRequest($tagTeam, $this->validParams())->assertRedirect(route('login'));
    }

    /** @test */
    public function update_validates_using_a_form_request()
    {
        $this->assertActionUsesFormRequest(
            TagTeamsController::class,
            'update',
            UpdateRequest::class
        );
    }

    /**
     * @test
     * @dataProvider administrators
     */
    public function deletes_a_tag_team_and_redirects($administrators)
    {
        $this->actAs($administrators);
        $tagTeam = TagTeam::factory()->create();

        $response = $this->deleteRequest($tagTeam);

        $response->assertRedirect(route('tag-teams.index'));
        $this->assertSoftDeleted($tagTeam);
    }

    /** @test */
    public function a_basic_user_cannot_delete_a_tag_team()
    {
        $this->actAs(Role::BASIC);
        $tagTeam = TagTeam::factory()->create();

        $this->deleteRequest($tagTeam)->assertForbidden();
    }

    /** @test */
    public function a_guest_cannot_delete_a_tag_team()
    {
        $tagTeam = TagTeam::factory()->create();

        $this->deleteRequest($tagTeam)->assertRedirect(route('login'));
    }
}
