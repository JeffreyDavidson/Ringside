<?php

namespace Tests\Feature\Admin\Managers;

use App\Models\Manager;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * @group managers
 * @group admins
 */
class RestoreManagerSuccessConditionsTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function an_administrator_can_restore_a_deleted_manager()
    {
        $this->actAs('administrator');
        $manager = factory(Manager::class)->create();
        $manager->delete();

        $response = $this->put(route('managers.restore', $manager));

        $response->assertRedirect(route('managers.index'));
        $this->assertNull($manager->fresh()->deleted_at);
    }
}
