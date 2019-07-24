<?php

namespace Tests\Feature\Generic\Manager;

use Tests\TestCase;
use App\Models\Manager;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * @group managers
 * @group generics
 */
class CreateManagerFailureConditionsTest extends TestCase
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
    public function a_manager_first_name_is_required()
    {
        $this->actAs('administrator');

        $response = $this->from(route('managers.create'))
                        ->post(route('managers.store'), $this->validParams(['first_name' => '']));

        $response->assertStatus(302);
        $response->assertRedirect(route('managers.create'));
        $response->assertSessionHasErrors('first_name');
        $this->assertEquals(0, Manager::count());
    }

    /** @test */
    public function a_manager_first_name_must_be_string()
    {
        $this->actAs('administrator');

        $response = $this->from(route('managers.create'))
                        ->post(route('managers.store'), $this->validParams(['first_name' => ['not-a-string']]));

        $response->assertStatus(302);
        $response->assertRedirect(route('managers.create'));
        $response->assertSessionHasErrors('first_name');
        $this->assertEquals(0, Manager::count());
    }

    /** @test */
    public function a_manager_last_name_is_required()
    {
        $this->actAs('administrator');

        $response = $this->from(route('managers.create'))
                        ->post(route('managers.store'), $this->validParams(['last_name' => '']));

        $response->assertStatus(302);
        $response->assertRedirect(route('managers.create'));
        $response->assertSessionHasErrors('last_name');
        $this->assertEquals(0, Manager::count());
    }

    /** @test */
    public function a_manager_last_name_must_be_a_string()
    {
        $this->actAs('administrator');

        $response = $this->from(route('managers.create'))
                        ->post(route('managers.store'), $this->validParams(['last_name' => ['not-a-string']]));

        $response->assertStatus(302);
        $response->assertRedirect(route('managers.create'));
        $response->assertSessionHasErrors('last_name');
        $this->assertEquals(0, Manager::count());
    }

    /** @test */
    public function a_manager_started_at_must_be_a_string_if_filled()
    {
        $this->actAs('administrator');

        $response = $this->from(route('managers.create'))
                        ->post(route('managers.store'), $this->validParams(['started_at' => ['not-a-string']]));

        $response->assertStatus(302);
        $response->assertRedirect(route('managers.create'));
        $response->assertSessionHasErrors('started_at');
        $this->assertEquals(0, Manager::count());
    }

    /** @test */
    public function a_manager_started_at_must_be_in_datetime_format_if_filled()
    {
        $this->actAs('administrator');

        $response = $this->from(route('managers.create'))
                        ->post(route('managers.store'), $this->validParams(['started_at' => now()->toDateString()]));

        $response->assertStatus(302);
        $response->assertRedirect(route('managers.create'));
        $response->assertSessionHasErrors('started_at');
        $this->assertEquals(0, Manager::count());
    }
}
