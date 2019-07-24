<?php

namespace Tests\Feature\Generic\Managers;

use Carbon\Carbon;
use Tests\TestCase;
use App\Models\Manager;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * @group managers
 * @group generics
 */
class UpdateManagerFailureConditionsTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Default attributes for model.
     *
     * @param  array  $overrides
     * @return array
     */
    private function oldAttributes($overrides = [])
    {
        return array_replace([
            'first_name' => 'Bill',
            'last_name' => 'Gates',
        ], $overrides);
    }

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
        $manager = factory(Manager::class)->states('bookable')->create($this->oldAttributes());

        $response = $this->from(route('managers.edit', $manager))
                        ->patch(route('managers.update', $manager), $this->validParams([
                            'first_name' => ''
                        ]));

        $response->assertRedirect(route('managers.edit', $manager));
        $response->assertSessionHasErrors('first_name');
        tap($manager->fresh(), function ($manager) {
            $this->assertEquals('Bill', $manager->first_name);
        });
    }

    /** @test */
    public function a_manager_first_name_must_be_a_string()
    {
        $this->actAs('administrator');
        $manager = factory(Manager::class)->states('bookable')->create($this->oldAttributes());

        $response = $this->from(route('managers.edit', $manager))
                        ->patch(route('managers.update', $manager), $this->validParams([
                            'first_name' => ['not-a-string']
                        ]));

        $response->assertRedirect(route('managers.edit', $manager));
        $response->assertSessionHasErrors('first_name');
        tap($manager->fresh(), function ($manager) {
            $this->assertEquals('Bill', $manager->first_name);
        });
    }

    /** @test */
    public function a_manager_last_name_is_required()
    {
        $this->actAs('administrator');
        $manager = factory(Manager::class)->states('bookable')->create($this->oldAttributes());

        $response = $this->from(route('managers.edit', $manager))
                        ->patch(route('managers.update', $manager), $this->validParams([
                            'last_name' => ''
                        ]));

        $response->assertRedirect(route('managers.edit', $manager));
        $response->assertSessionHasErrors('last_name');
        tap($manager->fresh(), function ($manager) {
            $this->assertEquals('Gates', $manager->last_name);
        });
    }

    /** @test */
    public function a_manager_last_name_must_be_a_string()
    {
        $this->actAs('administrator');
        $manager = factory(Manager::class)->states('bookable')->create($this->oldAttributes());

        $response = $this->from(route('managers.edit', $manager))
                        ->patch(route('managers.update', $manager), $this->validParams([
                            'last_name' => ['not-a-string']
                        ]));

        $response->assertRedirect(route('managers.edit', $manager));
        $response->assertSessionHasErrors('last_name');
        tap($manager->fresh(), function ($manager) {
            $this->assertEquals('Gates', $manager->last_name);
        });
    }

    /** @test */
    public function a_manager_started_at_date_must_be_a_string_if_filled()
    {
        $this->actAs('administrator');
        $manager = factory(Manager::class)->states('bookable')->create($this->oldAttributes());

        $response = $this->from(route('managers.edit', $manager))
                        ->patch(route('managers.update', $manager), $this->validParams([
                            'started_at' => ['not-a-string']
                        ]));

        $response->assertRedirect(route('managers.edit', $manager));
        $response->assertSessionHasErrors('started_at');
        tap($manager->fresh(), function ($manager) {
            $this->assertNotNull($manager->employment->started_at);
        });
    }

    /** @test */
    public function a_manager_started_at_must_be_a_datetime_format()
    {
        $this->actAs('administrator');
        $manager = factory(Manager::class)->states('bookable')->create($this->oldAttributes());

        $response = $this->from(route('managers.edit', $manager))
                        ->patch(route('managers.update', $manager), $this->validParams([
                            'started_at' => 'not-a-datetime'
                        ]));

        $response->assertRedirect(route('managers.edit', $manager));
        $response->assertSessionHasErrors('started_at');
        tap($manager->fresh(), function ($manager) {
            $this->assertNotNull($manager->employment->started_at);
        });
    }

    /** @test */
    public function a_manager_started_at_date_is_required_if_employment_start_date_is_set()
    {
        $this->actAs('administrator');
        $manager = factory(Manager::class)->states('bookable')->create($this->oldAttributes());

        $response = $this->from(route('managers.edit', $manager))
                        ->patch(route('managers.update', $manager), $this->validParams([
                            'started_at' => ''
                        ]));

        $response->assertRedirect(route('managers.edit', $manager));
        $response->assertSessionHasErrors('started_at');
        tap($manager->fresh(), function ($manager) {
            $this->assertNotNull($manager->employment->started_at);
        });
    }

    /** @test */
    public function a_manager_started_at_date_if_filled_must_be_before_or_equal_to_manager_employment_started_at_date_is_set()
    {
        $this->actAs('administrator');
        $manager = factory(Manager::class)->states('bookable')->create($this->oldAttributes());
        $manager->employment()->update(['started_at' => Carbon::yesterday()->toDateTimeString()]);

        $response = $this->from(route('managers.edit', $manager))
                        ->patch(route('managers.update', $manager), $this->validParams([
                            'started_at' => now()->toDateTimeString()
                        ]));

        $response->assertRedirect(route('managers.edit', $manager));
        $response->assertSessionHasErrors('started_at');
        tap($manager->fresh(), function ($manager) {
            $this->assertEquals(Carbon::yesterday()->toDateTimeString(), $manager->employment->started_at);
        });
    }
}
