<?php

namespace Tests\Feature\Http\Controllers\Referees;

use App\Enums\Role;
use App\Http\Controllers\Referees\RefereesController;
use App\Http\Requests\Referees\StoreRequest;
use App\Http\Requests\Referees\UpdateRequest;
use App\Models\Referee;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * @group referees
 * @group feature-referees
 * @group srm
 * @group feature-srm
 * @group roster
 * @group feature-roster
 */
class RefereeControllerTest extends TestCase
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

    /**
     * @test
     * @dataProvider administrators
     */
    public function index_returns_a_view($administrators)
    {
        $this->actAs($administrators);

        $response = $this->get(route('referees.index'));

        $response->assertOk();
        $response->assertViewIs('referees.index');
        $response->assertSeeLivewire('referees.employed-referees');
        $response->assertSeeLivewire('referees.future-employed-and-unemployed-referees');
        $response->assertSeeLivewire('referees.released-referees');
        $response->assertSeeLivewire('referees.suspended-referees');
        $response->assertSeeLivewire('referees.injured-referees');
        $response->assertSeeLivewire('referees.retired-referees');
    }

    /** @test */
    public function a_basic_user_cannot_view_referees_index_page()
    {
        $this->actAs(Role::BASIC);

        $this->get(route('referees.index'))->assertForbidden();
    }

    /** @test */
    public function a_guest_cannot_view_referees_index_page()
    {
        $this->get(route('referees.index'))->assertRedirect(route('login'));
    }

    /**
     * @test
     * @dataProvider administrators
     */
    public function create_returns_a_view($administrators)
    {
        $this->actAs($administrators);

        $response = $this->get(route('referees.create'));

        $response->assertViewIs('referees.create');
        $response->assertViewHas('referee', new Referee);
    }

    /** @test */
    public function a_basic_user_cannot_view_the_form_for_creating_a_referee()
    {
        $this->actAs(Role::BASIC);

        $this->get(route('referees.create'))->assertForbidden();
    }

    /** @test */
    public function a_guest_cannot_view_the_form_for_creating_a_referee()
    {
        $this->get(route('referees.create'))->assertRedirect(route('login'));
    }

    /**
     * @test
     * @dataProvider administrators
     */
    public function store_creates_a_referee_and_redirects($administrators)
    {
        $this->actAs($administrators);

        $response = $this->from(route('referees.create'))->post(route('referees.store', $this->validParams()));

        $response->assertRedirect(route('referees.index'));
        tap(Referee::first(), function ($referee) {
            $this->assertEquals('John', $referee->first_name);
            $this->assertEquals('Smith', $referee->last_name);
        });
    }

    /**
     * @test
     * @dataProvider administrators
     */
    public function an_employment_is_not_created_for_the_referee_if_started_at_is_filled_in_request($administrators)
    {
        $this->actAs($administrators);

        $this->from(route('referees.create'))->post(route('referees.store', $this->validParams(['started_at' => null])));

        tap(Referee::first(), function ($referee) {
            $this->assertCount(0, $referee->employments);
        });
    }

    /**
     * @test
     * @dataProvider administrators
     */
    public function an_employment_is_created_for_the_referee_if_started_at_is_filled_in_request($administrators)
    {
        $startedAt = now()->toDateTimeString();

        $this->actAs($administrators);

        $this->from(route('referees.create'))->post(route('referees.store', $this->validParams(['started_at' => $startedAt])));

        tap(Referee::first(), function ($referee) use ($startedAt) {
            $this->assertCount(1, $referee->employments);
            $this->assertEquals($startedAt, $referee->employments->first()->started_at->toDateTimeString());
        });
    }

    /** @test */
    public function a_basic_user_cannot_create_a_referee()
    {
        $this->actAs(Role::BASIC);

        $this->from(route('referees.create'))->post(route('referees.store'), $this->validParams())->assertForbidden();
    }

    /** @test */
    public function a_guest_cannot_create_a_referee()
    {
        $this->from(route('referees.create'))->post(route('referees.store'), $this->validParams())->assertRedirect(route('login'));
    }

    /** @test */
    public function store_validates_using_a_form_request()
    {
        $this->assertActionUsesFormRequest(RefereesController::class, 'store', StoreRequest::class);
    }

    /**
     * @test
     * @dataProvider administrators
     */
    public function show_returns_a_view($administrators)
    {
        $this->actAs($administrators);
        $referee = Referee::factory()->create();

        $response = $this->get(route('referees.show', $referee));

        $response->assertViewIs('referees.show');
        $this->assertTrue($response->data('referee')->is($referee));
    }

    /** @test */
    public function a_basic_user_cannot_view_a_referee_profile()
    {
        $this->actAs(Role::BASIC);
        $referee = Referee::factory()->create();

        $this->get(route('referees.show', $referee))->assertForbidden();
    }

    /** @test */
    public function a_guest_cannot_view_a_referee_profile()
    {
        $referee = Referee::factory()->create();

        $this->get(route('referees.show', $referee))->assertRedirect(route('login'));
    }

    /**
     * @test
     * @dataProvider administrators
     */
    public function edit_returns_a_view($administrators)
    {
        $this->actAs($administrators);
        $referee = Referee::factory()->create();

        $response = $this->get(route('referees.edit', $referee));

        $response->assertViewIs('referees.edit');
        $this->assertTrue($response->data('referee')->is($referee));
    }

    /** @test */
    public function a_basic_user_cannot_view_the_form_for_editing_a_referee()
    {
        $this->actAs(Role::BASIC);
        $referee = Referee::factory()->create();

        $this->get(route('referees.edit', $referee))->assertForbidden();
    }

    /** @test */
    public function a_guest_cannot_view_the_form_for_editing_a_referee()
    {
        $referee = Referee::factory()->create();

        $this->get(route('referees.edit', $referee))->assertRedirect(route('login'));
    }

    /**
     * @test
     * @dataProvider administrators
     */
    public function update_a_referee($administrators)
    {
        $this->actAs($administrators);
        $referee = Referee::factory()->create();

        $response = $this->from(route('referees.edit', $referee))->put(route('referees.update', $referee), $this->validParams());

        $response->assertRedirect(route('referees.index'));
        tap($referee->fresh(), function ($referee) {
            $this->assertEquals('John', $referee->first_name);
            $this->assertEquals('Smith', $referee->last_name);
        });
    }

    /**
     * @test
     * @dataProvider administrators
     */
    public function update_can_employ_an_unemployed_referee_when_started_at_is_filled($administrators)
    {
        $now = now()->toDateTimeString();
        $this->actAs($administrators);
        $referee = Referee::factory()->unemployed()->create();

        $response = $this->from(route('referees.edit', $referee))->put(route('referees.update', $referee), $this->validParams(['started_at' => $now]));

        $response->assertRedirect(route('referees.index'));
        tap($referee->fresh(), function ($referee) use ($now) {
            $this->assertCount(1, $referee->employments);
            $this->assertEquals($now, $referee->employments->first()->started_at->toDateTimeString());
        });
    }

    /**
     * @test
     * @dataProvider administrators
     */
    public function update_can_employ_a_future_employed_referee_when_started_at_is_filled($administrators)
    {
        $now = now()->toDateTimeString();
        $this->actAs($administrators);
        $referee = Referee::factory()->withFutureEmployment()->create();

        $response = $this->from(route('referees.edit', $referee))->put(route('referees.update', $referee), $this->validParams(['started_at' => $now]));

        $response->assertRedirect(route('referees.index'));
        tap($referee->fresh(), function ($referee) use ($now) {
            $this->assertCount(1, $referee->employments);
            $this->assertEquals($now, $referee->employments()->first()->started_at->toDateTimeString());
        });
    }

    /**
     * @test
     * @dataProvider administrators
     */
    public function update_can_employ_a_released_referee_when_started_at_is_filled($administrators)
    {
        $now = now()->toDateTimeString();
        $this->actAs($administrators);
        $referee = Referee::factory()->released()->create();

        $response = $this->from(route('referees.edit', $referee))->put(route('referees.update', $referee), $this->validParams(['started_at' => $now]));

        $response->assertRedirect(route('referees.index'));
        tap($referee->fresh(), function ($referee) use ($now) {
            $this->assertCount(2, $referee->employments);
            $this->assertEquals($now, $referee->employments->last()->started_at->toDateTimeString());
        });
    }

    /**
     * @test
     * @dataProvider administrators
     */
    public function updating_cannot_employ_a_bookable_referee_when_started_at_is_filled($administrators)
    {
        $this->actAs($administrators);
        $referee = Referee::factory()->bookable()->create();

        $response = $this->from(route('referees.edit', $referee))->put(route('referees.update', $referee), $this->validParams(['started_at' => $referee->employments()->first()->started_at->toDateTimeString()]));

        $response->assertRedirect(route('referees.index'));
        tap($referee->fresh(), function ($referee) {
            $this->assertCount(1, $referee->employments);
        });
    }

    /** @test */
    public function a_basic_user_cannot_update_a_referee()
    {
        $this->actAs(Role::BASIC);
        $referee = Referee::factory()->create();

        $this->from(route('referees.edit', $referee))->put(route('referees.update', $referee), $this->validParams())->assertForbidden();
    }

    /** @test */
    public function a_guest_cannot_update_a_referee()
    {
        $referee = Referee::factory()->create();

        $this->from(route('referees.edit', $referee))->put(route('referees.update', $referee), $this->validParams())->assertRedirect(route('login'));
    }

    /** @test */
    public function update_validates_using_a_form_request()
    {
        $this->assertActionUsesFormRequest(RefereesController::class, 'update', UpdateRequest::class);
    }

    /**
     * @test
     * @dataProvider administrators
     */
    public function an_administrator_can_delete_a_referee($administrators)
    {
        $this->actAs($administrators);
        $referee = Referee::factory()->create();

        $response = $this->delete(route('referees.destroy', $referee));

        $response->assertRedirect(route('referees.index'));
        $this->assertSoftDeleted($referee);
    }

    /** @test */
    public function a_basic_user_cannot_delete_a_referee()
    {
        $this->actAs(Role::BASIC);
        $referee = Referee::factory()->create();

        $this->delete(route('referees.destroy', $referee))->assertForbidden();
    }

    /** @test */
    public function a_guest_cannot_delete_a_referee()
    {
        $referee = Referee::factory()->create();

        $this->delete(route('referees.destroy', $referee))->assertRedirect(route('login'));
    }
}
