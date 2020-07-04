<?php

namespace Tests\Feature\Referees;

use App\Enums\Role;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Factories\RefereeFactory;
use Tests\TestCase;

/**
 * @group referees
 * @group roster
 */
class ViewRefereeBioPageFailureConditionsTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function a_basic_user_cannot_view_a_referee_profile()
    {
        $this->actAs(Role::BASIC);
        $referee = RefereeFactory::new()->create();

        $response = $this->showRequest($referee);

        $response->assertForbidden();
    }

    /** @test */
    public function a_guest_cannot_view_a_referee_profile()
    {
        $referee = RefereeFactory::new()->create();

        $response = $this->showRequest($referee);

        $response->assertRedirect(route('login'));
    }
}
