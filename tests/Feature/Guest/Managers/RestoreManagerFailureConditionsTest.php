<?php

namespace Tests\Feature\Guest\Managers;

use App\Models\Manager;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * @group managers
 * @group guests
 */
class RestoreManagerFailureConditionsTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function a_guest_cannot_restore_a_deleted_manager()
    {
        $manager = factory(Manager::class)->create();
        $manager->delete();

        $response = $this->put(route('managers.restore', $manager));

        $response->assertRedirect(route('login'));
    }
}
