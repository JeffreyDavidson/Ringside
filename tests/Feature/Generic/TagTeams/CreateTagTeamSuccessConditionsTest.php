<?php

namespace Tests\Feature\Generic\TagTeams;

use Tests\TestCase;
use App\Models\TagTeam;
use App\Models\Wrestler;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * @group tagteams
 * @group generics
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
        $wrestlers = factory(Wrestler::class, 2)->states('bookable')->create();

        return array_replace_recursive([
            'name' => 'Example Tag Team Name',
            'signature_move' => 'The Finisher',
            'started_at' => now()->toDateTimeString(),
            'wrestlers' => $overrides['wrestlers'] ?? $wrestlers->modelKeys(),
        ], $overrides);
    }

    /** @test */
    public function two_wrestlers_make_a_tag_team()
    {
        $this->actAs('administrator');

        $wrestlers = factory(Wrestler::class, 2)->states('bookable')->create()->modelKeys();

        $this->post(route('tagteams.store'), $this->validParams(['wrestlers' => $wrestlers]));

        tap(TagTeam::first(), function ($tagteam) use ($wrestlers) {
            $this->assertCount(2, $tagteam->wrestlers);
            $this->assertEquals($tagteam->wrestlers->modelKeys(), $wrestlers);
        });
    }

    /** @test */
    public function a_tag_team_name_is_optional()
    {
        $this->actAs('administrator');

        $response = $this->post(route('tagteams.store'), $this->validParams(['name' => '']));

        $response->assertSessionDoesntHaveErrors('name');
    }

    /** @test */
    public function a_tag_team_signature_move_is_optional()
    {
        $this->actAs('administrator');

        $response = $this->post(route('tagteams.store'), $this->validParams(['signature_move' => '']));

        $response->assertSessionDoesntHaveErrors('signature_move');
    }

    /** @test */
    public function a_tag_team_started_at_date_is_optional()
    {
        $this->actAs('administrator');

        $response = $this->post(route('tagteams.store'), $this->validParams(['started_at' => '']));

        $response->assertSessionDoesntHaveErrors('started_at');
    }
}
