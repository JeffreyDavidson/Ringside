<?php

namespace Tests\Feature\Admin\Managers;

use Tests\TestCase;
use App\Models\Manager;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * @group managers
 * @group admins
 */
class InjureManagerSuccessConditionsTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function an_administrator_can_injure_a_bookable_manager()
    {
        $this->actAs('administrator');
        $manager = factory(Manager::class)->states('bookable')->create();

        $response = $this->put(route('managers.injure', $manager));

        $response->assertRedirect(route('managers.index'));
        $this->assertEquals(now()->toDateTimeString(), $manager->fresh()->injury->started_at);
    }
}
