<?php

namespace Tests\Feature\User\Managers;

use Tests\TestCase;
use App\Models\Manager;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * @group managers
 * @group users
 */
class SuspendManagerFailureConditionsTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function a_basic_user_cannot_suspend_a_bookable_manager()
    {
        $this->actAs('basic-user');
        $manager = factory(Manager::class)->states('bookable')->create();

        $response = $this->put(route('managers.suspend', $manager));

        $response->assertForbidden();
    }
}
