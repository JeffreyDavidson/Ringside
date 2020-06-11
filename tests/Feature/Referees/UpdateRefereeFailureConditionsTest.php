<?php

namespace Tests\Feature\Referees;

use App\Enums\Role;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Factories\RefereeFactory;
use Tests\TestCase;

/**
 * @group referees
 * @group roster
 */
class UpdateRefereeFailureConditionsTest extends TestCase
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
    public function a_basic_user_cannot_view_the_form_for_editing_a_referee()
    {
        $this->actAs(Role::BASIC);
        $referee = RefereeFactory::new()->create();

        $response = $this->editRequest($referee);

        $response->assertForbidden();
    }

    /** @test */
    public function a_basic_user_cannot_update_a_referee()
    {
        $this->actAs(Role::BASIC);
        $referee = RefereeFactory::new()->create();

        $response = $this->updateRequest($referee, $this->validParams());

        $response->assertForbidden();
    }

    /** @test */
    public function a_guest_cannot_view_the_form_for_editing_a_referee()
    {
        $referee = RefereeFactory::new()->create();

        $response = $this->editRequest($referee);

        $response->assertRedirect(route('login'));
    }

    /** @test */
    public function a_guest_cannot_update_a_referee()
    {
        $referee = RefereeFactory::new()->create();

        $response = $this->updateRequest($referee, $this->validParams());

        $response->assertRedirect(route('login'));
    }

    /** @test */
    public function a_referee_first_name_is_required()
    {
        $this->actAs(Role::ADMINISTRATOR);
        $referee = RefereeFactory::new()->create(['first_name' => 'Bill']);

        $response = $this->updateRequest($referee, $this->validParams(['first_name' => '']));

        $response->assertRedirect(route('referees.edit', $referee));
        $response->assertSessionHasErrors('first_name');
        tap($referee->fresh(), function ($referee) {
            $this->assertEquals('Bill', $referee->first_name);
        });
    }

    /** @test */
    public function a_referee_first_name_must_be_a_string()
    {
        $this->actAs(Role::ADMINISTRATOR);
        $referee = RefereeFactory::new()->create(['first_name' => 'Bill']);

        $response = $this->updateRequest($referee, $this->validParams(['first_name' => ['not-a-string']]));

        $response->assertRedirect(route('referees.edit', $referee));
        $response->assertSessionHasErrors('first_name');
        tap($referee->fresh(), function ($referee) {
            $this->assertEquals('Bill', $referee->first_name);
        });
    }

    /** @test */
    public function a_referee_last_name_is_required()
    {
        $this->actAs(Role::ADMINISTRATOR);
        $referee = RefereeFactory::new()->create(['last_name' => 'Gates']);

        $response = $this->updateRequest($referee, $this->validParams(['last_name' => '']));

        $response->assertRedirect(route('referees.edit', $referee));
        $response->assertSessionHasErrors('last_name');
        tap($referee->fresh(), function ($referee) {
            $this->assertEquals('Gates', $referee->last_name);
        });
    }

    /** @test */
    public function a_referee_last_name_must_be_a_string()
    {
        $this->actAs(Role::ADMINISTRATOR);
        $referee = RefereeFactory::new()->create(['last_name' => 'Gates']);

        $response = $this->updateRequest($referee, $this->validParams(['last_name' => ['not-a-string']]));

        $response->assertRedirect(route('referees.edit', $referee));
        $response->assertSessionHasErrors('last_name');
        tap($referee->fresh(), function ($referee) {
            $this->assertEquals('Gates', $referee->last_name);
        });
    }

    /** @test */
    public function a_referee_started_at_date_is_required_if_employment_start_date_is_set()
    {
        $this->actAs(Role::ADMINISTRATOR);
        $referee = RefereeFactory::new()->bookable()->create();

        $response = $this->updateRequest($referee, $this->validParams(['started_at' => '']));

        $response->assertRedirect(route('referees.edit', $referee));
        $response->assertSessionHasErrors('started_at');
        tap($referee->fresh(), function ($referee) {
            $this->assertNotNull($referee->currentEmployment->started_at);
        });
    }

    /** @test */
    public function a_referee_started_at_date_if_filled_must_be_before_or_equal_to_referee_employment_started_at_date_is_set()
    {
        $this->actAs(Role::ADMINISTRATOR);
        $referee = RefereeFactory::new()->bookable()->create();
        $referee->currentEmployment()->update(['started_at' => Carbon::yesterday()->toDateTimeString()]);

        $response = $this->updateRequest($referee, $this->validParams(['started_at' => now()->toDateTimeString()]));

        $response->assertRedirect(route('referees.edit', $referee));
        $response->assertSessionHasErrors('started_at');
        tap($referee->fresh(), function ($referee) {
            $this->assertEquals(Carbon::yesterday()->toDateTimeString(), $referee->currentEmployment->started_at);
        });
    }

    /** @test */
    public function a_referee_started_at_date_must_be_a_string_if_filled()
    {
        $this->actAs(Role::ADMINISTRATOR);
        $referee = RefereeFactory::new()->bookable()->create();

        $response = $this->updateRequest($referee, $this->validParams(['started_at' => ['not-a-string']]));

        $response->assertRedirect(route('referees.edit', $referee));
        $response->assertSessionHasErrors('started_at');
        tap($referee->fresh(), function ($referee) {
            $this->assertNotNull($referee->currentEmployment->started_at);
        });
    }

    /** @test */
    public function a_referee_started_at_date_must_be_in_datetime_format_if_filled()
    {
        $this->actAs(Role::ADMINISTRATOR);
        $referee = RefereeFactory::new()->bookable()->create();

        $response = $this->updateRequest($referee, $this->validParams(['started_at' => now()->toDateString()]));

        $response->assertRedirect(route('referees.edit', $referee));
        $response->assertSessionHasErrors('started_at');
        tap($referee->fresh(), function ($referee) {
            $this->assertNotNull($referee->currentEmployment->started_at);
        });
    }
}
