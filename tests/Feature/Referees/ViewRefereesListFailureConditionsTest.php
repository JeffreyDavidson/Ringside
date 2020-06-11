<?php

namespace Tests\Feature\Referees;

use App\Enums\Role;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * @group referees
 * @group roster
 */
class ViewRefereesListFailureConditionsTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function a_basic_user_cannot_view_referees_page()
    {
        $this->actAs(Role::BASIC);

        $response = $this->indexRequest('referees');

        $response->assertForbidden();
    }

    /** @test */
    public function a_guest_cannot_view_referees_page()
    {
        $response = $this->indexRequest('referee');

        $response->assertRedirect(route('login'));
    }
}
