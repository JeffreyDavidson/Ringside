<?php

namespace Tests\Feature\TagTeams;

use App\Enums\Role;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Factories\TagTeamFactory;
use Tests\TestCase;
use Tests\Factories\WrestlerFactory;

/**
 * @group tagteams
 * @group roster
 */
class UpdateTagTeamSuccessConditionsTest extends TestCase
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

        return array_replace([
            'name' => 'Example Tag Team Name',
            'signature_move' => 'The Finisher',
            'started_at' => now()->toDateTimeString(),
            'wrestlers' => $overrides['wrestlers'] ?? $wrestlers->modelKeys(),
        ], $overrides);
    }

    /** @test */
    public function an_administrator_can_view_the_form_for_editing_a_tag_team()
    {
        $this->actAs(Role::ADMINISTRATOR);
        $tagTeam = TagTeamFactory::new()->create();

        $response = $this->editRequest($tagTeam);

        $response->assertViewIs('tagteams.edit');
        $this->assertTrue($response->data('tagTeam')->is($tagTeam));
    }

    /** @test */
    public function an_administrator_can_update_a_tag_team()
    {
        $this->actAs(Role::ADMINISTRATOR);
        $tagTeam = TagTeamFactory::new()->create();

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
        $tagTeam = TagTeamFactory::new()->bookable()->create();
        $wrestlers = WrestlerFactory::new()->count(2)->bookable()->create();

        // $this->updateRequest($tagTeam, $this->validParams([
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
    public function a_tag_team_signature_move_is_optional()
    {
        $this->actAs(Role::ADMINISTRATOR);
        $tagTeam = TagTeamFactory::new()->create();

        $response = $this->updateRequest($tagTeam, $this->validParams(['signature_move' => '']));

        $response->assertSessionDoesntHaveErrors('signature_move');
    }

    /** @test */
    public function a_tag_team_started_at_is_optional()
    {
        $this->actAs(Role::ADMINISTRATOR);
        $tagTeam = TagTeamFactory::new()->create();

        $response = $this->updateRequest($tagTeam, $this->validParams(['started_at' => '']));

        $response->assertSessionDoesntHaveErrors('started_at');
    }
}
