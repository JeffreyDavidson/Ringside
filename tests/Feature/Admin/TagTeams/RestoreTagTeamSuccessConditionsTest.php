<?php

namespace Tests\Feature\Admin\TagTeams;

use App\Models\TagTeam;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * @group tagteams
 * @group superadmins
 */
class RestoreTagTeamSuccessConditionsTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function an_administrator_can_restore_a_deleted_tag_team()
    {
        $this->actAs('administrator');
        $tagteam = factory(TagTeam::class)->create(['deleted_at' => today()->subDays(3)->toDateTimeString()]);

        $response = $this->put(route('tagteams.restore', $tagteam));

        $response->assertRedirect(route('tagteams.index'));
        $this->assertNull($tagteam->fresh()->deleted_at);
    }
}
