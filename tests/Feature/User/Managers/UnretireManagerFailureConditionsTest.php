<?php

namespace Tests\Feature\User\Managers;

use App\Models\Manager;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * @group managers
 * @group users
 */
class UnretireManagerFailureConditionsTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function a_basic_user_cannot_unretire_a_retired_manager()
    {
        $this->actAs('basic-user');
        $manager = factory(Manager::class)->states('retired')->create();

        $response = $this->put(route('managers.unretire', $manager));

        $response->assertForbidden();
    }
}
