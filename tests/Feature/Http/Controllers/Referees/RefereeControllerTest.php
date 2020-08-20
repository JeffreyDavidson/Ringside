<?php

namespace Tests\Feature\Http\Controllers\Referees;

use App\Enums\Role;
use App\Http\Controllers\Referees\RefereesController;
use App\Models\Referee;
use App\Http\Requests\Referees\StoreRequest;
use App\Http\Requests\Referees\UpdateRequest;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Factories\RefereeFactory;
use Tests\TestCase;

/**
 * @group referees
 * @group feature-referees
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

        $response = $this->indexRequest('referees');

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

        $this->indexRequest('referees')->assertForbidden();
    }

    /** @test */
    public function a_guest_cannot_view_referees_index_page()
    {
        $this->indexRequest('referee')->assertRedirect(route('login'));
    }

    /**
     * @test
     * @dataProvider administrators
     */
    public function create_returns_a_view($administrators)
    {
        $this->actAs($administrators);

        $response = $this->createRequest('referee');

        $response->assertViewIs('referees.create');
        $response->assertViewHas('referee', new Referee);
    }

    /**
     * @test
     * @dataProvider administrators
     */
    public function store_creates_a_referee_and_redirects($administrators)
    {
        $this->actAs($administrators);

        $response = $this->storeRequest('referee', $this->validParams());

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

        $this->storeRequest('referee', $this->validParams(['started_at' => null]));

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

        $this->storeRequest('referees', $this->validParams(['started_at' => $startedAt]));

        tap(Referee::first(), function ($referee) use ($startedAt) {
            $this->assertCount(1, $referee->employments);
            $this->assertEquals($startedAt, $referee->employments->first()->started_at->toDateTimeString());
        });
    }

    /** @test */
    public function a_basic_user_cannot_view_the_form_for_creating_a_referee()
    {
        $this->actAs(Role::BASIC);

        $this->createRequest('referee')->assertForbidden();
    }

    /** @test */
    public function a_basic_user_cannot_create_a_referee()
    {
        $this->actAs(Role::BASIC);

        $this->storeRequest('referee', $this->validParams())->assertForbidden();
    }

    /** @test */
    public function a_guest_cannot_view_the_form_for_creating_a_referee()
    {
        $response = $this->createRequest('referee');

        $response->assertRedirect(route('login'));
    }

    /** @test */
    public function a_guest_cannot_create_a_referee()
    {
        $this->storeRequest('referee', $this->validParams())->assertRedirect(route('login'));
    }

    /** @test */
    public function store_validates_using_a_form_request()
    {
        $this->assertActionUsesFormRequest(
            RefereesController::class,
            'store',
            StoreRequest::class
        );
    }

    /**
     * @test
     * @dataProvider administrators
     */
    public function show_returns_a_view($administrators)
    {
        $this->actAs($administrators);
        $referee = RefereeFactory::new()->create();

        $response = $this->showRequest($referee);

        $response->assertViewIs('referees.show');
        $this->assertTrue($response->data('referee')->is($referee));
    }

    /** @test */
    public function a_basic_user_cannot_view_a_referee_profile()
    {
        $this->actAs(Role::BASIC);
        $referee = RefereeFactory::new()->create();

        $this->showRequest($referee)->assertForbidden();
    }

    /** @test */
    public function a_guest_cannot_view_a_referee_profile()
    {
        $referee = RefereeFactory::new()->create();

        $this->showRequest($referee)->assertRedirect(route('login'));
    }

    /**
     * @test
     * @dataProvider administrators
     */
    public function edit_returns_a_view($administrators)
    {
        $this->actAs($administrators);
        $referee = RefereeFactory::new()->create();

        $response = $this->editRequest($referee);

        $response->assertViewIs('referees.edit');
        $this->assertTrue($response->data('referee')->is($referee));
    }

    /**
     * @test
     * @dataProvider administrators
     */
    public function update_a_referee($administrators)
    {
        $this->actAs($administrators);
        $referee = RefereeFactory::new()->create();

        $response = $this->updateRequest($referee, $this->validParams());

        $response->assertRedirect(route('referees.index'));
        tap($referee->fresh(), function ($referee) {
            $this->assertEquals('John', $referee->first_name);
            $this->assertEquals('Smith', $referee->last_name);
        });
    }

    /** @test */
    public function a_basic_user_cannot_view_the_form_for_editing_a_referee()
    {
        $this->actAs(Role::BASIC);
        $referee = RefereeFactory::new()->create();

        $this->editRequest($referee)->assertForbidden();
    }

    /** @test */
    public function a_basic_user_cannot_update_a_referee()
    {
        $this->actAs(Role::BASIC);
        $referee = RefereeFactory::new()->create();

        $this->updateRequest($referee, $this->validParams())->assertForbidden();
    }

    /** @test */
    public function a_guest_cannot_view_the_form_for_editing_a_referee()
    {
        $referee = RefereeFactory::new()->create();

        $this->editRequest($referee)->assertRedirect(route('login'));
    }

    /** @test */
    public function a_guest_cannot_update_a_referee()
    {
        $referee = RefereeFactory::new()->create();

        $this->updateRequest($referee, $this->validParams())->assertRedirect(route('login'));
    }

    /** @test */
    public function update_validates_using_a_form_request()
    {
        $this->assertActionUsesFormRequest(
            RefereesController::class,
            'update',
            UpdateRequest::class
        );
    }

    /**
     * @test
     * @dataProvider administrators
     */
    public function an_administrator_can_delete_a_referee($administrators)
    {
        $this->actAs($administrators);
        $referee = RefereeFactory::new()->create();

        $response = $this->deleteRequest($referee);

        $response->assertRedirect(route('referees.index'));
        $this->assertSoftDeleted($referee);
    }

    /** @test */
    public function a_basic_user_cannot_delete_a_referee()
    {
        $this->actAs(Role::BASIC);
        $referee = RefereeFactory::new()->create();

        $this->deleteRequest($referee)->assertForbidden();
    }

    /** @test */
    public function a_guest_cannot_delete_a_referee()
    {
        $referee = RefereeFactory::new()->create();

        $this->deleteRequest($referee)->assertRedirect(route('login'));
    }
}
