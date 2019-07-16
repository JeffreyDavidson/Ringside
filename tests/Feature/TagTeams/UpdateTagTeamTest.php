<?php

namespace Tests\Feature\TagTeams;

use App\Models\TagTeam;
use App\Models\Wrestler;
use Tests\TestCase;
use SebastianBergmann\Comparator\Factory;
use Illuminate\Foundation\Testing\RefreshDatabase;

class UpdateTagTeamTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Valid Parameters for request.
     *
     * @param  array $overrides
     * @return array
     */
    private function validParams($overrides = [])
    {
        $wrestlers = factory(Wrestler::class, 2)->create();

        return array_replace([
            'name' => 'Example Tag Team Name',
            'signature_move' => 'The Finisher',
            'hired_at' => now()->toDateTimeString(),
            'wrestlers' => $overrides['wrestlers'] ?? $wrestlers->modelKeys(),
        ], $overrides);
    }

    /** @test */
    public function an_administrator_can_view_the_form_for_editing_a_tagteam()
    {
        $this->actAs('administrator');
        $tagteam = factory(TagTeam::class)->create();

        $response = $this->get(route('tagteams.edit', $tagteam));

        $response->assertViewIs('tagteams.edit');
        $this->assertTrue($response->data('tagteam')->is($tagteam));
    }

    /** @test */
    public function a_basic_user_cannot_view_the_form_for_editing_a_tagteam()
    {
        $this->actAs('basic-user');
        $tagteam = factory(TagTeam::class)->create();

        $response = $this->get(route('tagteams.edit', $tagteam));

        $response->assertStatus(403);
    }

    /** @test */
    public function a_guest_cannot_view_the_form_for_editing_a_tagteam()
    {
        $tagteam = factory(TagTeam::class)->create();

        $response = $this->get(route('tagteams.edit', $tagteam));

        $response->assertRedirect('/login');
    }

    /** @test */
    public function an_administrator_can_update_a_tag_team()
    {
        $this->actAs('administrator');
        $tagteam = factory(TagTeam::class)->create();

        $response = $this->patch(route('tagteams.update', $tagteam), $this->validParams());

        $response->assertRedirect(route('tagteams.index'));
        tap($tagteam->fresh(), function ($tagteam) {
            $this->assertEquals('Example Tag Team Name', $tagteam->name);
            $this->assertEquals('The Finisher', $tagteam->signature_move);
        });
    }

    /** @test */
    public function wrestlers_of_tag_team_are_synced_when_tag_team_is_updated()
    {
        $this->actAs('administrator');
        $tagteam = factory(TagTeam::class)->create();
        $wrestlers = Factory(Wrestler::class, 2)->create();

        $response = $this->patch(route('tagteams.update', $tagteam), $this->validParams([
            'wrestlers' => $wrestlers->modelKeys(),
        ]));

        tap($tagteam->fresh()->wrestlers, function ($tagteamWrestlers) use ($wrestlers) {
            $this->assertCount(2, $tagteamWrestlers);
            $this->assertEquals($tagteamWrestlers->modelKeys(), $wrestlers->modelKeys());
        });
    }

    /** @test */
    public function a_basic_user_cannot_update_a_tagteam()
    {
        $this->actAs('basic-user');
        $tagteam = factory(TagTeam::class)->create();

        $response = $this->patch(route('tagteams.update', $tagteam), $this->validParams());

        $response->assertStatus(403);
    }

    /** @test */
    public function a_guest_cannot_update_a_tagteam()
    {
        $tagteam = factory(TagTeam::class)->create();

        $response = $this->patch(route('tagteams.update', $tagteam), $this->validParams());

        $response->assertRedirect('/login');
    }

    /** @test */
    public function a_tagteam_name_is_required()
    {
        $this->actAs('administrator');
        $tagteam = factory(TagTeam::class)->create();

        $response = $this->patch(route('tagteams.update', $tagteam), $this->validParams([
            'name' => ''
        ]));

        $response->assertStatus(302);
        $response->assertSessionHasErrors('name');
    }

    /** @test */
    public function a_tagteam_signature_move_is_optional()
    {
        $this->actAs('administrator');
        $tagteam = factory(TagTeam::class)->create();

        $response = $this->patch(route('tagteams.update', $tagteam), $this->validParams([
            'signature_move' => ''
        ]));

        $response->assertSessionDoesntHaveErrors('signature_move');
    }

    /** @test */
    public function a_tagteam_hired_at_date_is_required()
    {
        $this->actAs('administrator');
        $tagteam = factory(TagTeam::class)->create();

        $response = $this->patch(route('tagteams.update', $tagteam), $this->validParams([
            'hired_at' => ''
        ]));

        $response->assertStatus(302);
        $response->assertSessionHasErrors('hired_at');
    }

    /** @test */
    public function a_tagteam_hired_at_must_be_in_datetime_format()
    {
        $this->actAs('administrator');
        $tagteam = factory(TagTeam::class)->create();

        $response = $this->patch(route('tagteams.update', $tagteam), $this->validParams([
            'hired_at' => now()->toDateString()
        ]));

        $response->assertStatus(302);
        $response->assertSessionHasErrors('hired_at');
    }

    /** @test */
    public function a_tagteam_hired_at_must_be_a_datetime_format()
    {
        $this->actAs('administrator');
        $tagteam = factory(TagTeam::class)->create();

        $response = $this->patch(route('tagteams.update', $tagteam), $this->validParams([
            'hired_at' => 'not-a-datetime'
        ]));

        $response->assertStatus(302);
        $response->assertSessionHasErrors('hired_at');
    }
}
