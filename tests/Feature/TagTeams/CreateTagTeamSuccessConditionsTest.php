<?php

namespace Tests\Feature\TagTeams;

use App\Enums\Role;
use App\Models\TagTeam;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Factories\WrestlerFactory;
use Tests\TestCase;

/**
 * @group tagteams
 * @group roster
 */
class CreateTagTeamSuccessConditionsTest extends TestCase
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
        $wrestlers = WrestlerFactory::new()->count(2)->bookable()->create();

        return array_replace_recursive([
            'name' => 'Example Tag Team Name',
            'signature_move' => 'The Finisher',
            'started_at' => now()->toDateTimeString(),
            'wrestlers' => $overrides['wrestlers'] ?? $wrestlers->modelKeys(),
        ], $overrides);
    }

    /** @test */
    public function an_administrator_can_view_the_form_for_creating_a_tag_team()
    {
        $this->actAs(Role::ADMINISTRATOR);

        $response = $this->createRequest('tag-team');

        $response->assertViewIs('tagteams.create');
        $response->assertViewHas('tagTeam', new TagTeam);
    }

    /** @test */
    public function an_administrator_can_create_a_tag_team()
    {
        $this->actAs(Role::ADMINISTRATOR);

        $response = $this->storeRequest('tag-team', $this->validParams());

        $response->assertRedirect(route('tag-teams.index'));
        tap(TagTeam::first(), function ($tagTeam) {
            $this->assertEquals('Example Tag Team Name', $tagTeam->name);
            $this->assertEquals('The Finisher', $tagTeam->signature_move);
        });
    }

    /** @test */
    public function an_administrator_can_employ_a_tag_team_during_creation_with_a_valid_started_at()
    {
        $this->actAs(Role::ADMINISTRATOR);

        $this->storeRequest('tag-team', $this->validParams(['started_at' => now()->toDateTimeString()]));

        tap(TagTeam::first(), function ($tagTeam) {
            $this->assertCount(1, $tagTeam->employments);
        });
    }

    /** @test */
    public function an_administrator_can_create_a_tag_team_without_employing()
    {
        $this->actAs(Role::ADMINISTRATOR);

        $this->storeRequest('tag-team', $this->validParams(['started_at' => null]));

        tap(TagTeam::first(), function ($tagTeam) {
            $this->assertCount(0, $tagTeam->employments);
        });
    }

    /** @test */
    public function a_tag_team_signature_move_is_optional()
    {
        $this->actAs(Role::ADMINISTRATOR);

        $response = $this->storeRequest('tag-team', $this->validParams(['signature_move' => '']));

        $response->assertSessionDoesntHaveErrors('signature_move');
    }

    /** @test */
    public function a_tag_team_started_at_date_is_optional()
    {
        $this->actAs(Role::ADMINISTRATOR);

        $response = $this->storeRequest('tag-team', $this->validParams(['started_at' => '']));

        $response->assertSessionDoesntHaveErrors('started_at');
    }
}
