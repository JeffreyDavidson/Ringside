<?php

namespace Tests\Feature\Manager;

use Carbon\Carbon;
use Tests\TestCase;
use App\Models\Manager;
use Illuminate\Foundation\Testing\RefreshDatabase;

class CreateManagerTest extends TestCase
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
    public function an_administrator_can_view_the_form_for_creating_a_manager()
    {
        $this->actAs('administrator');

        $response = $this->get(route('managers.create'));

        $response->assertViewIs('managers.create');
    }

    /** @test */
    public function a_basic_user_cannot_view_the_form_for_creating_a_manager()
    {
        $this->actAs('basic-user');

        $response = $this->get(route('managers.create'));

        $response->assertStatus(403);
    }

    /** @test */
    public function a_guest_cannot_view_the_form_for_creating_a_manager()
    {
        $response = $this->get(route('managers.create'));

        $response->assertRedirect('/login');
    }

    /** @test */
    public function an_administrator_can_create_a_manager()
    {
        $this->actAs('administrator');

        $response = $this->post(route('managers.store'), $this->validParams());

        $response->assertRedirect(route('managers.index'));
        tap(Manager::first(), function ($manager) {
            $this->assertEquals('John', $manager->first_name);
            $this->assertEquals('Smith', $manager->last_name);
            $this->assertEquals(today()->toDateTimeString(), $manager->hired_at->toDateTimeString());
        });
    }

    /** @test */
    public function a_manager_slug_is_generated_when_created()
    {
        $this->actAs('administrator');

        $response = $this->post(route('managers.store'), $this->validParams());

        tap(Manager::first(), function ($manager) {
            $this->assertEquals('jsmith', $manager->slug);
        });
    }

    /** @test */
    public function a_manager_hired_today_or_before_is_active()
    {
        $this->actAs('administrator');

        $response = $this->post(route('managers.store'), $this->validParams(['hired_at' => today()->toDateTimeString()]));

        tap(Manager::first(), function ($manager) {
            $this->assertTrue($manager->is_active);
        });
    }

    /** @test */
    public function a_manager_hired_after_today_is_inactive()
    {
        $this->actAs('administrator');

        $response = $this->post(route('managers.store'), $this->validParams(['hired_at' => Carbon::tomorrow()->toDateTimeString()]));

        tap(Manager::first(), function ($manager) {
            $this->assertFalse($manager->is_active);
        });
    }

    /** @test */
    public function a_basic_user_cannot_create_a_manager()
    {
        $this->actAs('basic-user');

        $response = $this->post(route('managers.store'), $this->validParams());

        $response->assertStatus(403);
    }

    /** @test */
    public function a_guest_cannot_create_a_manager()
    {
        $response = $this->post(route('managers.store'), $this->validParams());

        $response->assertRedirect('/login');
    }

    /** @test */
    public function a_manager_first_name_is_required()
    {
        $this->actAs('administrator');

        $response = $this->post(route('managers.store'), $this->validParams(['first_name' => '']));

        $response->assertStatus(302);
        $response->assertSessionHasErrors('first_name');
    }

    /** @test */
    public function a_manager_last_name_is_required()
    {
        $this->actAs('administrator');

        $response = $this->post(route('managers.store'), $this->validParams(['last_name' => '']));

        $response->assertStatus(302);
        $response->assertSessionHasErrors('last_name');
    }

    /** @test */
    public function a_manager_hired_at_date_is_required()
    {
        $this->actAs('administrator');

        $response = $this->post(route('managers.store'), $this->validParams(['hired_at' => '']));

        $response->assertStatus(302);
        $response->assertSessionHasErrors('hired_at');
    }

    /** @test */
    public function a_manager_hired_at_must_be_in_datetime_format()
    {
        $this->actAs('administrator');

        $response = $this->post(route('managers.store'), $this->validParams(['hired_at' => today()->toDateString()]));

        $response->assertStatus(302);
        $response->assertSessionHasErrors('hired_at');
    }

    /** @test */
    public function a_manager_hired_at_must_be_a_datetime_format()
    {
        $this->actAs('administrator');

        $response = $this->post(route('managers.store'), $this->validParams(['hired_at' => 'not-a-datetime']));

        $response->assertStatus(302);
        $response->assertSessionHasErrors('hired_at');
    }
}
