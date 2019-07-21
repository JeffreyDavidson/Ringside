<?php

namespace Tests\Feature\Guest\Referees;

use App\Models\Referee;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * @group referees
 * @group guests
 */
class UnretireRefereeFailureConditionsTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function a_guest_cannot_unretire_a_retired_referee()
    {
        $referee = factory(Referee::class)->states('retired')->create();

        $response = $this->put(route('referees.unretire', $referee));

        $response->assertRedirect(route('login'));
    }
}
