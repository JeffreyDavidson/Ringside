<?php

namespace Tests\Feature\Http\Controllers\Wrestlers;

use App\Enums\Role;
use App\Http\Controllers\Wrestlers\WrestlersController;
use App\Http\Requests\Wrestlers\StoreRequest;
use App\Http\Requests\Wrestlers\UpdateRequest;
use App\Models\User;
use App\Models\Wrestler;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * @group wrestlers
 * @group feature-wrestlers
 * @group srm
 * @group feature-srm
 * @group roster
 * @group feature-roster
 */
class WrestlerControllerTest extends TestCase
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
            'name' => 'Example Wrestler Name',
            'feet' => 6,
            'inches' => 4,
            'weight' => 240,
            'hometown' => 'Laraville, FL',
            'signature_move' => 'The Finisher',
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

        $response = $this->get(route('wrestlers.index'));

        $response->assertOk();
        $response->assertViewIs('wrestlers.index');
        $response->assertSeeLivewire('wrestlers.bookable-wrestlers');
        $response->assertSeeLivewire('wrestlers.future-employed-and-unemployed-wrestlers');
        $response->assertSeeLivewire('wrestlers.released-wrestlers');
        $response->assertSeeLivewire('wrestlers.suspended-wrestlers');
        $response->assertSeeLivewire('wrestlers.injured-wrestlers');
        $response->assertSeeLivewire('wrestlers.retired-wrestlers');
    }

    /** @test */
    public function a_basic_user_cannot_view_wrestlers_index_page()
    {
        $this->actAs(Role::BASIC);

        $this->get(route('wrestlers.index'))->assertForbidden();
    }

    /** @test */
    public function a_guest_cannot_view_wrestlers_index_page()
    {
        $this->get(route('wrestlers.index'))->assertRedirect(route('login'));
    }

    /**
     * @test
     * @dataProvider administrators
     */
    public function create_returns_a_view($administrators)
    {
        $this->actAs($administrators);

        $response = $this->get(route('wrestlers.create'));

        $response->assertViewIs('wrestlers.create');
        $response->assertViewHas('wrestler', new Wrestler);
    }

    /** @test */
    public function a_basic_user_cannot_view_the_form_for_creating_a_wrestler()
    {
        $this->actAs(Role::BASIC);

        $this->get(route('wrestlers.create'))->assertForbidden();
    }

    /** @test */
    public function a_guest_cannot_view_the_form_for_creating_a_wrestler()
    {
        $this->get(route('wrestlers.create'))->assertRedirect(route('login'));
    }

    /**
     * @test
     * @dataProvider administrators
     */
    public function store_creates_a_wrestler_and_redirects($administrators)
    {
        $this->actAs($administrators);

        $response = $this->from(route('wrestlers.create'))->post(route('wrestlers.store'), $this->validParams());

        $response->assertRedirect(route('wrestlers.index'));
        tap(Wrestler::first(), function ($wrestler) {
            $this->assertEquals('Example Wrestler Name', $wrestler->name);
            $this->assertEquals(76, $wrestler->height);
            $this->assertEquals(240, $wrestler->weight);
            $this->assertEquals('Laraville, FL', $wrestler->hometown);
            $this->assertEquals('The Finisher', $wrestler->signature_move);
        });
    }

    /**
     * @test
     * @dataProvider administrators
     */
    public function an_employment_is_not_created_for_the_wrestler_if_started_at_is_filled_in_request($administrators)
    {
        $this->actAs($administrators);

        $this->from(route('wrestlers.create'))->post(route('wrestlers.store'), $this->validParams(['started_at' => null]));

        tap(Wrestler::first(), function ($wrestler) {
            $this->assertCount(0, $wrestler->employments);
        });
    }

    /**
     * @test
     * @dataProvider administrators
     */
    public function an_employment_is_created_for_the_wrestler_if_started_at_is_filled_in_request($administrators)
    {
        $startedAt = now();

        $this->actAs($administrators);

        $this->from(route('wrestlers.create'))->post(route('wrestlers.store'), $this->validParams(['started_at' => $startedAt->toDateTimeString()]));

        tap(Wrestler::first(), function ($wrestler) use ($startedAt) {
            $this->assertCount(1, $wrestler->employments);
            $this->assertEquals($startedAt->toDateTimeString('minute'), $wrestler->employments->first()->started_at->toDateTimeString('minute'));
        });
    }

    /** @test */
    public function a_basic_user_cannot_create_a_wrestler()
    {
        $this->actAs(Role::BASIC);

        $this->from(route('wrestlers.create'))->post(route('wrestlers.store'), $this->validParams())->assertForbidden();
    }

    /** @test */
    public function a_guest_cannot_create_a_wrestler()
    {
        $this->from(route('wrestlers.create'))->post(route('wrestlers.store'), $this->validParams())->assertRedirect(route('login'));
    }

    /** @test */
    public function store_validates_using_a_form_request()
    {
        $this->assertActionUsesFormRequest(WrestlersController::class, 'store', StoreRequest::class);
    }

    /**
     * @test
     * @dataProvider administrators
     */
    public function show_returns_a_view($administrators)
    {
        $this->actAs($administrators);
        $wrestler = Wrestler::factory()->create();

        $response = $this->get(route('wrestlers.show', $wrestler));

        $response->assertViewIs('wrestlers.show');
        $this->assertTrue($response->data('wrestler')->is($wrestler));
    }

    /** @test */
    public function a_basic_user_can_view_their_wrestler_profile()
    {
        $signedInUser = $this->actAs(Role::BASIC);
        $wrestler = Wrestler::factory()->create(['user_id' => $signedInUser->id]);

        $this->get(route('wrestlers.show', $wrestler))->assertOk();
    }

    /** @test */
    public function a_basic_user_cannot_view_another_users_wrestler_profile()
    {
        $this->actAs(Role::BASIC);
        $otherUser = User::factory()->create();
        $wrestler = Wrestler::factory()->create(['user_id' => $otherUser->id]);

        $this->get(route('wrestlers.show', $wrestler))->assertForbidden();
    }

    /** @test */
    public function a_guest_cannot_view_a_wrestler_profile()
    {
        $wrestler = Wrestler::factory()->create();

        $this->get(route('wrestlers.show', $wrestler))->assertRedirect(route('login'));
    }

    /**
     * @test
     * @dataProvider administrators
     */
    public function edit_returns_a_view($administrators)
    {
        $this->actAs($administrators);
        $wrestler = Wrestler::factory()->create();

        $response = $this->get(route('wrestlers.edit', $wrestler));

        $response->assertViewIs('wrestlers.edit');
        $this->assertTrue($response->data('wrestler')->is($wrestler));
    }

    /** @test */
    public function a_basic_user_cannot_view_the_form_for_editing_a_wrestler()
    {
        $this->actAs(Role::BASIC);
        $wrestler = Wrestler::factory()->create();

        $this->get(route('wrestlers.edit', $wrestler))->assertForbidden();
    }

    /** @test */
    public function a_guest_cannot_view_the_form_for_editing_a_wrestler()
    {
        $wrestler = Wrestler::factory()->create();

        $this->get(route('wrestlers.edit', $wrestler))->assertRedirect(route('login'));
    }

    /**
     * @test
     * @dataProvider administrators
     */
    public function updates_a_wrestler_and_redirects($administrators)
    {
        $this->actAs($administrators);
        $wrestler = Wrestler::factory()->create();

        $response = $this->from(route('wrestlers.edit', $wrestler))->patch(route('wrestlers.update', $wrestler), $this->validParams());

        $response->assertRedirect(route('wrestlers.index'));
        tap($wrestler->fresh(), function ($wrestler) {
            $this->assertEquals('Example Wrestler Name', $wrestler->name);
            $this->assertEquals(76, $wrestler->height);
            $this->assertEquals(240, $wrestler->weight);
            $this->assertEquals('Laraville, FL', $wrestler->hometown);
            $this->assertEquals('The Finisher', $wrestler->signature_move);
        });
    }

    /**
     * @test
     * @dataProvider administrators
     */
    public function update_can_employ_an_unemployed_wrestler_when_started_at_is_filled($administrators)
    {
        $now = now();
        $this->actAs($administrators);
        $wrestler = Wrestler::factory()->unemployed()->create();

        $response = $this->from(route('wrestlers.edit', $wrestler))->patch(route('wrestlers.update', $wrestler), $this->validParams(['started_at' => $now->toDateTimeString()]));

        $response->assertRedirect(route('wrestlers.index'));
        tap($wrestler->fresh(), function ($wrestler) use ($now) {
            $this->assertCount(1, $wrestler->employments);
            $this->assertEquals($now->toDateTimeString('minute'), $wrestler->employments->first()->started_at->toDateTimeString('minute'));
        });
    }

    /**
     * @test
     * @dataProvider administrators
     */
    public function update_can_employ_a_future_employed_wrestler_when_started_at_is_filled($administrators)
    {
        $now = now();
        $this->actAs($administrators);
        $wrestler = Wrestler::factory()->withFutureEmployment()->create();

        $response = $this->from(route('wrestlers.edit', $wrestler))->patch(route('wrestlers.update', $wrestler), $this->validParams(['started_at' => $now->toDateTimeString()]));

        $response->assertRedirect(route('wrestlers.index'));
        tap($wrestler->fresh(), function ($wrestler) use ($now) {
            $this->assertCount(1, $wrestler->employments);
            $this->assertEquals($now->toDateTimeString('minute'), $wrestler->employments()->first()->started_at->toDateTimeString('minute'));
        });
    }

    /**
     * @test
     * @dataProvider administrators
     */
    public function update_cannot_employ_a_released_wrestler_when_started_at_is_filled($administrators)
    {
        $now = now();
        $this->actAs($administrators);
        $wrestler = Wrestler::factory()->released()->create();

        $response = $this->from(route('wrestlers.edit', $wrestler))->patch(route('wrestlers.update', $wrestler), $this->validParams(['started_at' => $wrestler->employments()->first()->started_at->toDateTimeString()]));

        $response->assertRedirect(route('wrestlers.index'));
        tap($wrestler->fresh(), function ($wrestler) {
            $this->assertCount(1, $wrestler->employments);
        });
    }

    /**
     * @test
     * @dataProvider administrators
     */
    public function updating_cannot_employ_a_bookable_wrestler_when_started_at_is_filled($administrators)
    {
        $this->actAs($administrators);
        $wrestler = Wrestler::factory()->bookable()->create();

        $response = $this->from(route('wrestlers.edit', $wrestler))->patch(route('wrestlers.update', $wrestler), $this->validParams(['started_at' => $wrestler->employments()->first()->started_at->toDateTimeString()]));

        $response->assertRedirect(route('wrestlers.index'));
        tap($wrestler->fresh(), function ($wrestler) {
            $this->assertCount(1, $wrestler->employments);
        });
    }

    /** @test */
    public function a_basic_user_cannot_update_a_wrestler()
    {
        $this->actAs(Role::BASIC);
        $wrestler = Wrestler::factory()->create();

        $this->from(route('wrestlers.edit', $wrestler))->patch(route('wrestlers.update', $wrestler), $this->validParams())->assertForbidden();
    }

    /** @test */
    public function a_guest_cannot_update_a_wrestler()
    {
        $wrestler = Wrestler::factory()->create();

        $this->from(route('wrestlers.edit', $wrestler))->patch(route('wrestlers.update', $wrestler), $this->validParams())->assertRedirect(route('login'));
    }

    /** @test */
    public function update_validates_using_a_form_request()
    {
        $this->assertActionUsesFormRequest(WrestlersController::class, 'update', UpdateRequest::class);
    }

    /**
     * @test
     * @dataProvider administrators
     */
    public function deletes_a_wrestler_and_redirects($administrators)
    {
        $this->actAs($administrators);
        $wrestler = Wrestler::factory()->create();

        $response = $this->delete(route('wrestlers.destroy', $wrestler));

        $response->assertRedirect(route('wrestlers.index'));
        $this->assertSoftDeleted($wrestler);
    }

    /** @test */
    public function a_basic_user_cannot_delete_a_wrestler()
    {
        $this->actAs(Role::BASIC);
        $wrestler = Wrestler::factory()->create();

        $this->delete(route('wrestlers.destroy', $wrestler))->assertForbidden();
    }

    /** @test */
    public function a_guest_cannot_delete_a_wrestler()
    {
        $wrestler = Wrestler::factory()->create();

        $this->delete(route('wrestlers.destroy', $wrestler))->assertRedirect(route('login'));
    }
}
