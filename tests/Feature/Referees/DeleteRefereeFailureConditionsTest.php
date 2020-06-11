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
class DeleteRefereeFailureConditionsTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function a_basic_user_cannot_delete_a_bookable_referee()
    {
        $this->actAs(Role::BASIC);
        $referee = RefereeFactory::new()->create();

        $response = $this->deleteRequest($referee);

        $response->assertForbidden();
    }

    /** @test */
    public function a_guest_cannot_delete_a_referee()
    {
        $referee = RefereeFactory::new()->create();

        $response = $this->deleteRequest($referee);

        $response->assertRedirect(route('login'));
    }

    /** @test */
    public function an_already_deleted_wrestler_cannot_be_deleted()
    {
        $this->actAs(Role::ADMINISTRATOR);
        $referee = RefereeFactory::new()->softDeleted()->create();

        $response = $this->deleteRequest($referee);

        $response->assertNotFound();
    }
}
