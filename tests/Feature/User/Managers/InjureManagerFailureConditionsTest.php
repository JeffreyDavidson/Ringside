<?php

namespace Tests\Feature\User\Managers;

use App\Models\Manager;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * @group managers
 * @group users
 */
class InjureManagerFailureConditionsTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function a_basic_user_cannot_injure_a_bookable_manager()
    {
        $this->actAs('basic-user');
        $manager = factory(Manager::class)->states('bookable')->create();

        $response = $this->put(route('managers.injure', $manager));

        $response->assertForbidden();
    }
}
