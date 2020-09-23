<?php

namespace Tests\Feature\Http\Controllers\Managers;

use App\Enums\Role;
use App\Http\Controllers\Managers\ManagersController;
use App\Http\Requests\Managers\StoreRequest;
use App\Http\Requests\Managers\UpdateRequest;
use App\Models\Manager;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * @group managers
 * @group feature-managers
 * @group srm
 * @group feature-srm
 * @group roster
 * @group feature-roster
 */
class ManagerControllerTest extends TestCase
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
    public function index_returns_a_views($administrators)
    {
        $this->actAs($administrators);

        $response = $this->indexRequest('managers');

        $response->assertOk();
        $response->assertViewIs('managers.index');
        $response->assertSeeLivewire('managers.employed-managers');
        $response->assertSeeLivewire('managers.future-employed-and-unemployed-managers');
        $response->assertSeeLivewire('managers.released-managers');
        $response->assertSeeLivewire('managers.suspended-managers');
        $response->assertSeeLivewire('managers.injured-managers');
        $response->assertSeeLivewire('managers.retired-managers');
    }

    /** @test */
    public function a_basic_user_cannot_view_managers_index_page()
    {
        $this->actAs(Role::BASIC);

        $this->indexRequest('managers')->assertForbidden();
    }

    /** @test */
    public function a_guest_cannot_view_managers_index_page()
    {
        $this->indexRequest('manager')->assertRedirect(route('login'));
    }

    /**
     * @test
     * @dataProvider administrators
     */
    public function create_returns_a_view($administrators)
    {
        $this->actAs($administrators);

        $response = $this->createRequest('manager');

        $response->assertViewIs('managers.create');
        $response->assertViewHas('manager', new Manager);
    }

    /**
     * @test
     * @dataProvider administrators
     */
    public function store_creates_a_manager_and_redirects($administrators)
    {
        $now = now();
        Carbon::setTestNow($now);

        $this->actAs($administrators);

        $response = $this->storeRequest('manager', $this->validParams());

        $response->assertRedirect(route('managers.index'));
        tap(Manager::first(), function ($manager) {
            $this->assertEquals('John', $manager->first_name);
            $this->assertEquals('Smith', $manager->last_name);
        });
    }

    /**
     * @test
     * @dataProvider administrators
     */
    public function an_employment_is_not_created_for_the_manager_if_started_at_is_filled_in_request($administrators)
    {
        $this->actAs($administrators);

        $this->storeRequest('manager', $this->validParams(['started_at' => null]));

        tap(Manager::first(), function ($manager) {
            $this->assertCount(0, $manager->employments);
        });
    }

    /**
     * @test
     * @dataProvider administrators
     */
    public function an_employment_is_created_for_the_manager_if_started_at_is_filled_in_request($administrators)
    {
        $startedAt = now()->toDateTimeString();

        $this->actAs($administrators);

        $this->storeRequest('managers', $this->validParams(['started_at' => $startedAt]));

        tap(Manager::first(), function ($manager) use ($startedAt) {
            $this->assertCount(1, $manager->employments);
            $this->assertEquals($startedAt, $manager->employments->first()->started_at->toDateTimeString());
        });
    }

    /** @test */
    public function a_basic_user_cannot_view_the_form_for_creating_a_manager()
    {
        $this->actAs(Role::BASIC);

        $this->createRequest('manager')->assertForbidden();
    }

    /** @test */
    public function a_basic_user_cannot_create_a_manager()
    {
        $this->actAs(Role::BASIC);

        $this->storeRequest('manager', $this->validParams())->assertForbidden();
    }

    /** @test */
    public function a_guest_cannot_view_the_form_for_creating_a_manager()
    {
        $this->createRequest('manager')->assertRedirect(route('login'));
    }

    /** @test */
    public function a_guest_cannot_create_a_manager()
    {
        $this->storeRequest('manager', $this->validParams())->assertRedirect(route('login'));
    }

    /** @test */
    public function store_validates_using_a_form_request()
    {
        $this->assertActionUsesFormRequest(
            ManagersController::class,
            'store',
            StoreRequest::class
        );
    }

    /**
     * @test
     * @dataProvider administrators
     */
    public function show_can_view_a_manager_profile($administrators)
    {
        $this->actAs($administrators);
        $manager = Manager::factory()->create();

        $response = $this->showRequest($manager);

        $response->assertViewIs('managers.show');
        $this->assertTrue($response->data('manager')->is($manager));
    }

    /** @test */
    public function a_basic_user_can_view_their_manager_profile()
    {
        $signedInUser = $this->actAs(Role::BASIC);
        $manager = Manager::factory()->create(['user_id' => $signedInUser->id]);

        $this->showRequest($manager)->assertOk();
    }

    /** @test */
    public function a_basic_user_cannot_view_another_users_manager_profile()
    {
        $this->actAs(Role::BASIC);
        $otherUser = User::factory()->create();
        $manager = Manager::factory()->create(['user_id' => $otherUser->id]);

        $response = $this->showRequest($manager);

        $response->assertForbidden();
    }

    /** @test */
    public function a_guest_cannot_view_a_manager_profile()
    {
        $manager = Manager::factory()->create();

        $this->showRequest($manager)->assertRedirect(route('login'));
    }

    /**
     * @test
     * @dataProvider administrators
     */
    public function edit_returns_a_view($administrators)
    {
        $this->actAs($administrators);
        $manager = Manager::factory()->create();

        $response = $this->editRequest($manager);

        $response->assertViewIs('managers.edit');
        $this->assertTrue($response->data('manager')->is($manager));
    }

    /**
     * @test
     * @dataProvider administrators
     */
    public function update_a_manage_and_redirects($administrators)
    {
        $this->actAs($administrators);
        $manager = Manager::factory()->create();

        $response = $this->updateRequest($manager, $this->validParams());

        $response->assertRedirect(route('managers.index'));
        tap($manager->fresh(), function ($manager) {
            $this->assertEquals('John', $manager->first_name);
            $this->assertEquals('Smith', $manager->last_name);
        });
    }

    /**
     * @test
     * @dataProvider administrators
     */
    public function update_can_employ_an_unemployed_manager_when_started_at_is_filled($administrators)
    {
        $now = now()->toDateTimeString();
        $this->actAs($administrators);
        $manager = Manager::factory()->unemployed()->create();

        $response = $this->updateRequest($manager, $this->validParams(['started_at' => $now]));

        $response->assertRedirect(route('managers.index'));
        tap($manager->fresh(), function ($manager) use ($now) {
            $this->assertCount(1, $manager->employments);
            $this->assertEquals($now, $manager->employments->first()->started_at->toDateTimeString());
        });
    }

    /**
     * @test
     * @dataProvider administrators
     */
    public function update_can_employ_a_future_employed_manager_when_started_at_is_filled($administrators)
    {
        $now = now()->toDateTimeString();
        $this->actAs($administrators);
        $manager = Manager::factory()->withFutureEmployment()->create();

        $response = $this->updateRequest($manager, $this->validParams(['started_at' => $now]));

        $response->assertRedirect(route('managers.index'));
        tap($manager->fresh(), function ($manager) use ($now) {
            $this->assertCount(1, $manager->employments);
            $this->assertEquals($now, $manager->employments()->first()->started_at->toDateTimeString());
        });
    }

    /**
     * @test
     * @dataProvider administrators
     */
    public function update_can_employ_a_released_manager_when_started_at_is_filled($administrators)
    {
        $now = now()->toDateTimeString();
        $this->actAs($administrators);
        $manager = Manager::factory()->released()->create();

        $response = $this->updateRequest($manager, $this->validParams(['started_at' => $now]));

        $response->assertRedirect(route('managers.index'));
        tap($manager->fresh(), function ($manager) use ($now) {
            $this->assertCount(2, $manager->employments);
            $this->assertEquals($now, $manager->employments->last()->started_at->toDateTimeString());
        });
    }

    /**
     * @test
     * @dataProvider administrators
     */
    public function updating_cannot_employ_an_available_manager_when_started_at_is_filled($administrators)
    {
        $this->actAs($administrators);
        $manager = Manager::factory()->available()->create();

        $response = $this->updateRequest($manager, $this->validParams(['started_at' => $manager->employments()->first()->started_at->toDateTimeString()]));

        $response->assertRedirect(route('managers.index'));
        tap($manager->fresh(), function ($manager) {
            $this->assertCount(1, $manager->employments);
        });
    }

    /** @test */
    public function a_basic_user_cannot_view_the_form_for_editing_a_manager()
    {
        $this->actAs(Role::BASIC);
        $manager = Manager::factory()->create();

        $this->editRequest($manager)->assertForbidden();
    }

    /** @test */
    public function a_basic_user_cannot_update_a_manager()
    {
        $this->actAs(Role::BASIC);
        $manager = Manager::factory()->create();

        $this->updateRequest($manager, $this->validParams())->assertForbidden();
    }

    /** @test */
    public function a_guest_cannot_view_the_form_for_editing_a_manager()
    {
        $manager = Manager::factory()->create();

        $this->editRequest($manager)->assertRedirect(route('login'));
    }

    /** @test */
    public function a_guest_cannot_update_a_manager()
    {
        $manager = Manager::factory()->create();

        $this->updateRequest($manager, $this->validParams())->assertRedirect(route('login'));
    }

    /** @test */
    public function update_validates_using_a_form_request()
    {
        $this->assertActionUsesFormRequest(
            ManagersController::class,
            'update',
            UpdateRequest::class
        );
    }

    /**
     * @test
     * @dataProvider administrators
     */
    public function delete_a_manager_and_redirects($administrators)
    {
        $this->actAs($administrators);
        $manager = Manager::factory()->create();

        $response = $this->deleteRequest($manager);

        $response->assertRedirect(route('managers.index'));
        $this->assertSoftDeleted($manager);
    }

    /** @test */
    public function a_basic_user_cannot_delete_a_manager()
    {
        $this->actAs(Role::BASIC);
        $manager = Manager::factory()->create();

        $this->deleteRequest($manager)->assertForbidden();
    }

    /** @test */
    public function a_guest_cannot_delete_a_manager()
    {
        $manager = Manager::factory()->create();

        $this->deleteRequest($manager)->assertRedirect(route('login'));
    }
}
