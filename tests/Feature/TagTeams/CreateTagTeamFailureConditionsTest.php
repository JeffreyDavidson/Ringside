<?php

namespace Tests\Feature\TagTeams;

use App\Enums\Role;
use App\Models\TagTeam;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Factories\EmploymentFactory;
use Tests\Factories\TagTeamFactory;
use Tests\Factories\WrestlerFactory;
use Tests\TestCase;

/**
 * @group tagteams
 * @group roster
 */
class CreateTagTeamFailureConditionsTest extends TestCase
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
        $wrestlers = WrestlerFactory::new()->count(2)->create();

        return array_replace_recursive([
            'name' => 'Example Tag Team Name',
            'signature_move' => 'The Finisher',
            'started_at' => now()->toDateTimeString(),
            'wrestlers' => $overrides['wrestlers'] ?? $wrestlers->modelKeys(),
        ], $overrides);
    }

    /** @test */
    public function a_basic_user_cannot_view_the_form_for_creating_a_tag_team()
    {
        $this->actAs(Role::BASIC);

        $response = $this->createRequest('tag-teams');

        $response->assertForbidden();
    }

    /** @test */
    public function a_basic_user_cannot_create_a_tag_team()
    {
        $this->actAs(Role::BASIC);

        $response = $this->storeRequest('tag-team', $this->validParams());

        $response->assertForbidden();
    }

    /** @test */
    public function a_guest_cannot_view_the_form_for_creating_a_tag_team()
    {
        $response = $this->createRequest('tag-team');

        $response->assertRedirect(route('login'));
    }

    /** @test */
    public function a_guest_cannot_create_a_tag_team()
    {
        $response = $this->storeRequest('tag-team', $this->validParams());

        $response->assertRedirect(route('login'));
    }

    /** @test */
    public function a_tag_team_name_is_required()
    {
        $this->actAs(Role::ADMINISTRATOR);

        $response = $this->storeRequest('tag-team', $this->validParams([
            'name' => null,
        ]));

        $response->assertStatus(302);
        $response->assertRedirect(route('tag-teams.create'));
        $response->assertSessionHasErrors('name');
        $this->assertEquals(0, TagTeam::count());
    }

    /** @test */
    public function a_tag_team_name_must_be_a_string()
    {
        $this->actAs(Role::ADMINISTRATOR);

        $response = $this->storeRequest('tag-team', $this->validParams([
            'name' => ['not-a-string'],
        ]));

        $response->assertStatus(302);
        $response->assertRedirect(route('tag-teams.create'));
        $response->assertSessionHasErrors('name');
        $this->assertEquals(0, TagTeam::count());
    }

    /** @test */
    public function a_tag_team_name_must_be_a_unique()
    {
        $this->actAs(Role::ADMINISTRATOR);
        TagTeamFactory::new()->create(['name' => 'Example Tag Team Name']);

        $response = $this->storeRequest('tag-team', $this->validParams([
            'name' => 'Example Tag Team Name',
        ]));

        $response->assertStatus(302);
        $response->assertRedirect(route('tag-teams.create'));
        $response->assertSessionHasErrors('name');
        $this->assertEquals(1, TagTeam::count());
    }

    /** @test */
    public function a_tag_team_signature_move_must_be_a_string_if_filled()
    {
        $this->actAs(Role::ADMINISTRATOR);

        $response = $this->storeRequest('tag-team', $this->validParams([
            'signature_move' => ['not-a-string'],
        ]));

        $response->assertStatus(302);
        $response->assertRedirect(route('tag-teams.create'));
        $response->assertSessionHasErrors('signature_move');
        $this->assertEquals(0, TagTeam::count());
    }

    /** @test */
    public function a_tag_team_started_at_date_must_be_a_string_if_filled()
    {
        $this->actAs(Role::ADMINISTRATOR);

        $response = $this->storeRequest('tag-team', $this->validParams([
            'started_at' => ['not-a-string'],
        ]));

        $response->assertStatus(302);
        $response->assertRedirect(route('tag-teams.create'));
        $response->assertSessionHasErrors('started_at');
        $this->assertEquals(0, TagTeam::count());
    }

    /** @test */
    public function a_tag_team_started_at_date_must_be_in_datetime_format_if_filled()
    {
        $this->actAs(Role::ADMINISTRATOR);

        $response = $this->storeRequest('tag-team', $this->validParams([
            'started_at' => now()->toDateString(),
        ]));

        $response->assertStatus(302);
        $response->assertRedirect(route('tag-teams.create'));
        $response->assertSessionHasErrors('started_at');
        $this->assertEquals(0, TagTeam::count());
    }

    /** @test */
    public function a_tag_team_wrestlers_is_required_if_started_at_is_valid()
    {
        $this->actAs(Role::ADMINISTRATOR);

        $response = $this->storeRequest('tag-team', $this->validParams([
            'started_at' => now()->toDateTimeString(),
            'wrestlers' => null,
        ]));

        $response->assertStatus(302);
        $response->assertRedirect(route('tag-teams.create'));
        $response->assertSessionHasErrors('wrestlers');
        $this->assertEquals(0, TagTeam::count());
    }

    /** @test */
    public function a_tag_team_wrestlers_must_be_an_array_if_filled()
    {
        $this->actAs(Role::ADMINISTRATOR);

        $response = $this->storeRequest('tag-team', $this->validParams([
            'wrestlers' => 'not-an-array',
        ]));

        $response->assertStatus(302);
        $response->assertRedirect(route('tag-teams.create'));
        $response->assertSessionHasErrors('wrestlers');
        $this->assertEquals(0, TagTeam::count());
    }

    /** @test */
    public function a_tag_team_must_contain_a_max_of_two_wrestlers()
    {
        $this->actAs(Role::ADMINISTRATOR);
        $wrestlers = WrestlerFactory::new()->count(3)->create();

        $response = $this->storeRequest('tag-team', $this->validParams([
            'wrestlers' => $wrestlers->modelKeys(),
        ]));

        $response->assertStatus(302);
        $response->assertRedirect(route('tag-teams.create'));
        $response->assertSessionHasErrors('wrestlers');
        $this->assertEquals(0, TagTeam::count());
    }

    /** @test */
    public function each_value_in_the_wrestlers_array_must_be_an_integer()
    {
        $this->actAs(Role::ADMINISTRATOR);

        $response = $this->storeRequest('tag-team', $this->validParams([
            'wrestlers' => ['not-an-integer'],
        ]));

        $response->assertStatus(302);
        $response->assertRedirect(route('tag-teams.create'));
        $response->assertSessionHasErrors('wrestlers.*');
        $this->assertEquals(0, TagTeam::count());
    }

    /** @test */
    public function each_value_in_the_wrestlers_array_must_exist_in_the_wrestlers_table()
    {
        $this->actAs(Role::ADMINISTRATOR);

        $response = $this->storeRequest('tag-team', $this->validParams([
            'wrestlers' => [99],
        ]));

        $response->assertStatus(302);
        $response->assertRedirect(route('tag-teams.create'));
        $response->assertSessionHasErrors('wrestlers.*');
        $this->assertEquals(0, TagTeam::count());
    }

    /** @test */
    public function a_wrestler_can_have_a_pending_employment_status_to_join_a_tag_team_as_long_as_wrestler_started_at_date_is_before_tag_team_started_at_date()
    {
        $this->actAs(Role::ADMINISTRATOR);
        $wrestler = WrestlerFactory::new()->pendingEmployment(
            EmploymentFactory::new()->started(now()->addDays(2)->toDateTimeString())
        )->create();

        $response = $this->storeRequest('tag-team', $this->validParams([
            'started_at' => now()->addDays(3)->toDateTimeString(),
            'wrestlers' => [$wrestler->id],
        ]));

        $response->assertStatus(302);
        $response->assertRedirect(route('tag-teams.index'));
        $response->assertSessionDoesntHaveErrors('wrestlers.*');
    }

    /** @test */
    public function a_wrestler_cannot_be_a_part_of_more_than_one_bookable_tag_team()
    {
        $this->actAs(Role::ADMINISTRATOR);
        $tagTeam = TagTeamFactory::new()->bookable()->create();

        $response = $this->storeRequest('tag-team', $this->validParams([
            'wrestlers' => [$tagTeam->currentWrestlers->first()->id],
        ]));

        $response->assertStatus(302);
        $response->assertRedirect(route('tag-teams.create'));
        $response->assertSessionHasErrors('wrestlers.*');
        $this->assertEquals(1, TagTeam::count());
    }
}
