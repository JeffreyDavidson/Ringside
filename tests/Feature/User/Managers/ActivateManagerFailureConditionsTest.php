<?php

namespace Tests\Feature\User\Manager;

use Tests\TestCase;
use App\Models\Manager;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * @group managers
 * @group users
 */
class ActivateManagerFailureConditionsTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function a_basic_user_cannot_activate_a_pending_introduced_manager()
    {
        $this->actAs('basic-user');
        $manager = factory(Manager::class)->states('pending-introduction')->create();

        $response = $this->put(route('managers.activate', $manager));

        $response->assertForbidden();
    }
}
