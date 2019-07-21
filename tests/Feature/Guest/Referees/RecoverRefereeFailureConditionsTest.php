<?php

namespace Tests\Feature\Guest\Referees;

use App\Models\Referee;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * @group referees
 * @group guests
 */
class RecoverRefereeFailureConditionsTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function a_guest_cannot_recover_an_injured_referee()
    {
        $referee = factory(Referee::class)->states('injured')->create();

        $response = $this->put(route('referees.recover', $referee));

        $response->assertRedirect(route('login'));
    }
}
