<?php

namespace Tests\Feature\SuperAdmin\Managers;

use Tests\TestCase;
use App\Models\Manager;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * @group managers
 * @group superadmins
 */
class CreateManagerSuccessConditionsTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Valid parameters for request.
     *
     * @param  array $overrides
     * @return array
     */
    private function validParams($overrides = [])
    {
        return array_replace([
            'first_name' => 'John',
            'last_name' => 'Smith',
            'started_at' => now()->toDateTimeString(),
        ], $overrides);
    }

    /** @test */
    public function a_super_administrator_can_view_the_form_for_creating_a_manager()
    {
        $this->actAs('super-administrator');

        $response = $this->get(route('managers.create'));

        $response->assertViewIs('managers.create');
        $response->assertViewHas('manager', new Manager);
    }

    /** @test */
    public function a_super_administrator_can_create_a_manager()
    {
        $this->actAs('super-administrator');

        $response = $this->post(route('managers.store'), $this->validParams());

        $response->assertRedirect(route('managers.index'));
        tap(Manager::first(), function ($manager) {
            $this->assertEquals('John', $manager->first_name);
            $this->assertEquals('Smith', $manager->last_name);
            $this->assertEquals(now()->toDateTimeString(), $manager->employment->started_at);
        });
    }
}
