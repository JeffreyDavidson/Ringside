<?php

namespace Tests\Feature\Generic\Manager;

use Carbon\Carbon;
use Tests\TestCase;
use App\Models\Manager;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * @group managers
 * @group generics
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
    public function a_manager_started_today_or_before_is_bookable()
    {
        $this->actAs('administrator');

        $this->post(route('managers.store'), $this->validParams([
            'started_at' => today()->toDateTimeString()
        ]));

        tap(Manager::first(), function ($manager) {
            $this->assertTrue($manager->is_bookable);
        });
    }

    /** @test */
    public function a_manager_started_after_today_is_pending_introduction()
    {
        $this->actAs('administrator');

        $this->post(route('managers.store'), $this->validParams([
            'started_at' => Carbon::tomorrow()->toDateTimeString()
        ]));

        tap(Manager::first(), function ($manager) {
            $this->assertFalse($manager->is_bookable);
        });
    }

    /** @test */
    public function a_manager_started_at_date_is_optional()
    {
        $this->actAs('administrator');

        $response = $this->from(route('managers.create'))
                        ->post(route('managers.store'), $this->validParams([
                            'started_at' => ''
                        ]));

        $response->assertSessionDoesntHaveErrors('started_at');
        tap(Manager::first(), function ($manager) {
            $this->assertEmpty($manager->employments);
        });
    }
}
