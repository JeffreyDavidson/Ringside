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
class RestoreRefereeFailureConditionsTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function a_basic_user_cannot_restore_a_deleted_referee()
    {
        $this->actAs(Role::BASIC);
        $referee = RefereeFactory::new()->softDeleted()->create();

        $response = $this->restoreRequest($referee);

        $response->assertForbidden();
    }

    /** @test */
    public function a_guest_cannot_restore_a_deleted_referee()
    {
        $referee = RefereeFactory::new()->softDeleted()->create();

        $response = $this->restoreRequest($referee);

        $response->assertRedirect(route('login'));
    }

    /** @test */
    public function a_bookable_referee_cannot_be_restored()
    {
        $this->actAs(Role::ADMINISTRATOR);
        $referee = RefereeFactory::new()->bookable()->create();

        $response = $this->restoreRequest($referee);

        $response->assertNotFound();
    }

    /** @test */
    public function a_suspended_referee_cannot_be_restored()
    {
        $this->actAs(Role::ADMINISTRATOR);
        $referee = RefereeFactory::new()->suspended()->create();

        $response = $this->restoreRequest($referee);

        $response->assertNotFound();
    }

    /** @test */
    public function a_retired_referee_cannot_be_restored()
    {
        $this->actAs(Role::ADMINISTRATOR);
        $referee = RefereeFactory::new()->retired()->create();

        $response = $this->restoreRequest($referee);

        $response->assertNotFound();
    }

    /** @test */
    public function a_pending_employment_referee_cannot_be_restored()
    {
        $this->actAs(Role::ADMINISTRATOR);
        $referee = RefereeFactory::new()->pendingEmployment()->create();

        $response = $this->restoreRequest($referee);

        $response->assertNotFound();
    }

    /** @test */
    public function an_injured_referee_cannot_be_restored()
    {
        $this->actAs(Role::ADMINISTRATOR);
        $referee = RefereeFactory::new()->injured()->create();

        $response = $this->restoreRequest($referee);

        $response->assertNotFound();
    }
}
