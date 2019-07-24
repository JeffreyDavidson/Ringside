<?php

namespace Tests\Feature\User\Managers;

use App\Models\Manager;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * @group managers
 * @group users
 */
class RecoverManagerTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function a_basic_user_cannot_recover_an_injured_manager()
    {
        $this->actAs('basic-user');
        $manager = factory(Manager::class)->states('injured')->create();

        $response = $this->put(route('managers.recover', $manager));

        $response->assertForbidden();
    }
}
