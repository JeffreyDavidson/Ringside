<?php

namespace Tests\Feature\Guest\Referees;

use App\Models\Referee;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * @group referees
 * @group guests
 */
class DeleteRefereeFailureConditionsTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function a_guest_cannot_delete_a_bookable_referee()
    {
        $referee = factory(Referee::class)->states('bookable')->create();

        $response = $this->delete(route('referees.destroy', $referee));

        $response->assertRedirect(route('login'));
    }

    /** @test */
    public function a_guest_cannot_delete_a_pending_introduced_referee()
    {
        $referee = factory(Referee::class)->states('pending-introduced')->create();

        $response = $this->delete(route('referees.destroy', $referee));

        $response->assertRedirect(route('login'));
    }

    /** @test */
    public function a_guest_cannot_delete_a_retired_referee()
    {
        $referee = factory(Referee::class)->states('retired')->create();

        $response = $this->delete(route('referees.destroy', $referee));

        $response->assertRedirect(route('login'));
    }

    /** @test */
    public function a_guest_cannot_delete_a_suspended_referee()
    {
        $referee = factory(Referee::class)->states('suspended')->create();

        $response = $this->delete(route('referees.destroy', $referee));

        $response->assertRedirect(route('login'));
    }

    /** @test */
    public function a_guest_cannot_delete_an_injured_referee()
    {
        $referee = factory(Referee::class)->states('injured')->create();

        $response = $this->delete(route('referees.destroy', $referee));

        $response->assertRedirect(route('login'));
    }
}
