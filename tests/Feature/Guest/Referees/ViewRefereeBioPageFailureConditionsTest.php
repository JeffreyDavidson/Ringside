<?php

namespace Tests\Feature\Guest\Referees;

use App\Models\Referee;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * @group referees
 * @group guests
 */
class ViewRefereeBioPageFailureConditionsTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function a_guest_cannot_view_a_referee_profile()
    {
        $referee = factory(Referee::class)->create();

        $response = $this->get(route('referees.show', ['referee' => $referee]));

        $response->assertRedirect(route('login'));
    }
}