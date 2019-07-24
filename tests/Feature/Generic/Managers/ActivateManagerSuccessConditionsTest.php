<?php

namespace Tests\Feature\Generic\Manager;

use Tests\TestCase;
use App\Models\Manager;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * @group managers
 * @group generics
 */
class ActivateManagerSuccessConditionsTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function a_manager_without_a_current_employment_can_be_activated()
    {
        $this->actAs('administrator');
        $manager = factory(Manager::class)->create();

        $response = $this->put(route('managers.activate', $manager));

        $response->assertRedirect(route('managers.index'));
        tap($manager->fresh(), function ($manager) {
            $this->assertTrue($manager->is_bookable);
        });
    }
}
