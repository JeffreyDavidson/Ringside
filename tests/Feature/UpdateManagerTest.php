<?php

namespace Tests\Feature;

use App\Manager;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class UpdateManagerTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Valid Parameters for request.
     *
     * @param  array $overrides
     * @return array
     */
    private function validParams($overrides = [])
    {
        return array_replace([
            'first_name' => 'John',
            'last_name' => 'Smith',
            'hired_at' => today()->toDateTimeString(),
        ], $overrides);
    }

    /** @test */
    public function an_administrator_can_view_the_form_for_editing_a_manager()
    {
        $this->actAs('administrator');
        $manager = factory(Manager::class)->create();

        $response = $this->get(route('managers.edit', $manager));

        $response->assertViewIs('managers.edit');
        $this->assertTrue($response->data('manager')->is($manager));
    }

    /** @test */
    public function a_basic_user_cannot_view_the_form_for_editing_a_manager()
    {
        $this->actAs('basic-user');
        $manager = factory(Manager::class)->create();

        $response = $this->get(route('managers.edit', $manager));

        $response->assertStatus(403);
    }

    /** @test */
    public function a_guest_cannot_view_the_form_for_editing_a_manager()
    {
        $manager = factory(Manager::class)->create();

        $response = $this->get(route('managers.edit', $manager));

        $response->assertRedirect('/login');
    }

    /** @test */
    public function an_administrator_can_update_a_manager()
    {
        $this->actAs('administrator');
        $manager = factory(Manager::class)->create();

        $response = $this->patch(route('managers.update', $manager), $this->validParams());

        $response->assertRedirect(route('managers.index'));
        tap($manager->fresh(), function ($manager) {
            $this->assertEquals('John', $manager->first_name);
            $this->assertEquals('Smith', $manager->last_name);
            $this->assertEquals(today()->toDateTimeString(), $manager->hired_at->toDateTimeString());
        });
    }

    /** @test */
    public function a_basic_user_cannot_update_a_manager()
    {
        $this->actAs('basic-user');
        $manager = factory(Manager::class)->create();

        $response = $this->patch(route('managers.update', $manager), $this->validParams());

        $response->assertStatus(403);
    }

    /** @test */
    public function a_guest_cannot_update_a_manager()
    {
        $manager = factory(Manager::class)->create();

        $response = $this->patch(route('managers.update', $manager), $this->validParams());

        $response->assertRedirect('/login');
    }

    /** @test */
    public function a_manager_first_name_is_required()
    {
        $this->actAs('administrator');
        $manager = factory(Manager::class)->create();

        $response = $this->patch(route('managers.update', $manager), $this->validParams([
            'first_name' => ''
        ]));

        $response->assertStatus(302);
        $response->assertSessionHasErrors('first_name');
    }

    /** @test */
    public function a_manager_last_name_is_required()
    {
        $this->actAs('administrator');
        $manager = factory(Manager::class)->create();

        $response = $this->patch(route('managers.update', $manager), $this->validParams([
            'last_name' => ''
        ]));

        $response->assertStatus(302);
        $response->assertSessionHasErrors('last_name');
    }

    /** @test */
    public function a_manager_hired_at_date_is_required()
    {
        $this->actAs('administrator');
        $manager = factory(Manager::class)->create();

        $response = $this->patch(route('managers.update', $manager), $this->validParams([
            'hired_at' => ''
        ]));

        $response->assertStatus(302);
        $response->assertSessionHasErrors('hired_at');
    }

    /** @test */
    public function a_manager_hired_at_must_be_in_datetime_format()
    {
        $this->actAs('administrator');
        $manager = factory(Manager::class)->create();

        $response = $this->patch(route('managers.update', $manager), $this->validParams([
            'hired_at' => today()->toDateString()
        ]));

        $response->assertStatus(302);
        $response->assertSessionHasErrors('hired_at');
    }

    /** @test */
    public function a_manager_hired_at_must_be_a_datetime_format()
    {
        $this->actAs('administrator');
        $manager = factory(Manager::class)->create();

        $response = $this->patch(route('managers.update', $manager), $this->validParams([
            'hired_at' => 'not-a-datetime'
        ]));

        $response->assertStatus(302);
        $response->assertSessionHasErrors('hired_at');
    }
}
