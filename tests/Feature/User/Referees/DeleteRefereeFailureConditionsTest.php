<?php

namespace Tests\Feature\User\Referees;

use App\Models\Referee;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * @group referees
 * @group users
 */
class DeleteRefereeFailureConditionsTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function a_basic_user_cannot_delete_a_bookable_referee()
    {
        $this->actAs('basic-user');
        $referee = factory(Referee::class)->states('bookable')->create();

        $response = $this->delete(route('referees.destroy', $referee));

        $response->assertForbidden();
    }

    /** @test */
    public function a_basic_user_cannot_delete_a_pending_introduction_referee()
    {
        $this->actAs('basic-user');
        $referee = factory(Referee::class)->states('pending-introduction')->create();

        $response = $this->delete(route('referees.destroy', $referee));

        $response->assertForbidden();
    }

    /** @test */
    public function a_basic_user_cannot_delete_a_suspended_referee()
    {
        $this->actAs('basic-user');
        $referee = factory(Referee::class)->states('suspended')->create();

        $response = $this->delete(route('referees.destroy', $referee));

        $response->assertForbidden();
    }

    /** @test */
    public function a_basic_user_cannot_delete_an_injured_referee()
    {
        $this->actAs('basic-user');
        $referee = factory(Referee::class)->states('injured')->create();

        $response = $this->delete(route('referees.destroy', $referee));

        $response->assertForbidden();
    }

    /** @test */
    public function a_basic_user_cannot_delete_a_retired_referee()
    {
        $this->actAs('basic-user');
        $referee = factory(Referee::class)->states('retired')->create();

        $response = $this->delete(route('referees.destroy', $referee));

        $response->assertForbidden();
    }
}
