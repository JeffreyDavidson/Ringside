<?php

namespace Tests\Feature\Generic\Referees;

use Carbon\Carbon;
use Tests\TestCase;
use App\Models\Referee;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * @group referees
 * @group generics
 */
class UpdateRefereeFailureConditionsTest extends TestCase
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
    public function a_referee_first_name_is_required()
    {
        $this->actAs('administrator');
        $referee = factory(Referee::class)->create($this->oldAttributes());

        $response = $this->from(route('referees.edit', $referee))
                        ->patch(route('referees.update', $referee), $this->validParams([
                            'first_name' => ''
                        ]));

        $response->assertRedirect(route('referees.edit', $referee));
        $response->assertSessionHasErrors('first_name');
        tap($referee->fresh(), function ($referee) {
            $this->assertEquals('Bill', $referee->first_name);
        });
    }

    /** @test */
    public function a_referee_first_name_must_be_a_string()
    {
        $this->actAs('administrator');
        $referee = factory(Referee::class)->create($this->oldAttributes());

        $response = $this->from(route('referees.edit', $referee))
                        ->patch(route('referees.update', $referee), $this->validParams([
                            'first_name' => ['not-a-string']
                        ]));

        $response->assertRedirect(route('referees.edit', $referee));
        $response->assertSessionHasErrors('first_name');
        tap($referee->fresh(), function ($referee) {
            $this->assertEquals('Bill', $referee->first_name);
        });
    }

    /** @test */
    public function a_referee_last_name_is_required()
    {
        $this->actAs('administrator');
        $referee = factory(Referee::class)->create($this->oldAttributes());

        $response = $this->from(route('referees.edit', $referee))
                        ->patch(route('referees.update', $referee), $this->validParams([
                            'last_name' => ''
                        ]));

        $response->assertRedirect(route('referees.edit', $referee));
        $response->assertSessionHasErrors('last_name');
        tap($referee->fresh(), function ($referee) {
            $this->assertEquals('Gates', $referee->last_name);
        });
    }

    /** @test */
    public function a_referee_last_name_must_be_a_string()
    {
        $this->actAs('administrator');
        $referee = factory(Referee::class)->create($this->oldAttributes());

        $response = $this->from(route('referees.edit', $referee))
                        ->patch(route('referees.update', $referee), $this->validParams([
                            'last_name' => ['not-a-string']
                        ]));

        $response->assertRedirect(route('referees.edit', $referee));
        $response->assertSessionHasErrors('last_name');
        tap($referee->fresh(), function ($referee) {
            $this->assertEquals('Gates', $referee->last_name);
        });
    }

    /** @test */
    public function a_referee_started_at_date_is_required_if_employment_start_date_is_set()
    {
        $this->actAs('administrator');
        $referee = factory(Referee::class)->states('bookable')->create($this->oldAttributes());

        $response = $this->from(route('referees.edit', $referee))
                        ->patch(route('referees.update', $referee), $this->validParams([
                            'started_at' => ''
                        ]));

        $response->assertRedirect(route('referees.edit', $referee));
        $response->assertSessionHasErrors('started_at');
        tap($referee->fresh(), function ($referee) {
            $this->assertNotNull($referee->employment->started_at);
        });
    }

    /** @test */
    public function a_referee_started_at_date_if_filled_must_be_before_or_equal_to_referee_employment_started_at_date_is_set()
    {
        $this->actAs('administrator');
        $referee = factory(Referee::class)->states('bookable')->create($this->oldAttributes());
        $referee->employment()->update(['started_at' => Carbon::yesterday()->toDateTimeString()]);

        $response = $this->from(route('referees.edit', $referee))
                        ->patch(route('referees.update', $referee), $this->validParams([
                            'started_at' => now()->toDateTimeString()
                        ]));

        $response->assertRedirect(route('referees.edit', $referee));
        $response->assertSessionHasErrors('started_at');
        tap($referee->fresh(), function ($referee) {
            $this->assertEquals(Carbon::yesterday()->toDateTimeString(), $referee->employment->started_at);
        });
    }

    /** @test */
    public function a_referee_started_at_date_must_be_a_string_if_filled()
    {
        $this->actAs('administrator');
        $referee = factory(Referee::class)->states('bookable')->create($this->oldAttributes());

        $response = $this->from(route('referees.edit', $referee))
                        ->patch(route('referees.update', $referee), $this->validParams([
                            'started_at' => ['not-a-string']
                        ]));

        $response->assertRedirect(route('referees.edit', $referee));
        $response->assertSessionHasErrors('started_at');
        tap($referee->fresh(), function ($referee) {
            $this->assertNotNull($referee->employment->started_at);
        });
    }

    /** @test */
    public function a_referee_started_at_date_must_be_in_datetime_format_if_filled()
    {
        $this->actAs('administrator');
        $referee = factory(Referee::class)->states('bookable')->create($this->oldAttributes());

        $response = $this->from(route('referees.edit', $referee))
                        ->patch(route('referees.update', $referee), $this->validParams([
                            'started_at' => now()->toDateString()
                        ]));

        $response->assertRedirect(route('referees.edit', $referee));
        $response->assertSessionHasErrors('started_at');
        tap($referee->fresh(), function ($referee) {
            $this->assertNotNull($referee->employment->started_at);
        });
    }
}
