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
        $this->actAs($administrators)
            ->get(route('managers.index'))
            ->assertOk()
            ->assertViewIs('managers.index')
            ->assertSeeLivewire('managers.employed-managers')
            ->assertSeeLivewire('managers.future-employed-and-unemployed-managers')
            ->assertSeeLivewire('managers.released-managers')
            ->assertSeeLivewire('managers.suspended-managers')
            ->assertSeeLivewire('managers.injured-managers')
            ->assertSeeLivewire('managers.retired-managers');
    }

    /** @test */
    public function a_basic_user_cannot_view_managers_index_page()
    {
        $this->actAs(Role::BASIC)
            ->get(route('managers.index'))
            ->assertForbidden();
    }

    /** @test */
    public function a_guest_cannot_view_managers_index_page()
    {
        $this->get(route('managers.index'))
            ->assertRedirect(route('login'));
    }

    /**
     * @test
     * @dataProvider administrators
     */
    public function create_returns_a_view($administrators)
    {
        $this->actAs($administrators)
            ->get(route('managers.create'))
            ->assertViewIs('managers.create')
            ->assertViewHas('manager', new Manager);
    }

    /** @test */
    public function a_basic_user_cannot_view_the_form_for_creating_a_manager()
    {
        $this->actAs(Role::BASIC)
            ->get(route('managers.create'))
            ->assertForbidden();
    }

    /** @test */
    public function a_guest_cannot_view_the_form_for_creating_a_manager()
    {
        $this->get(route('managers.create'))
            ->assertRedirect(route('login'));
    }

    /**
     * @test
     * @dataProvider administrators
     */
    public function store_creates_a_manager_and_redirects($administrators)
    {
        $now = now();
        Carbon::setTestNow($now);

        $this->actAs($administrators)
            ->from(route('managers.create'))
            ->post(route('managers.store', $this->validParams()))
            ->assertRedirect(route('managers.index'));

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
        $this->actAs($administrators)
            ->from(route('managers.create'))
            ->post(route('managers.store', $this->validParams(['started_at' => null])));

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

        $this->actAs($administrators)
            ->from(route('managers.create'))
            ->post(route('managers.store', $this->validParams(['started_at' => $startedAt])));

        tap(Manager::first(), function ($manager) use ($startedAt) {
            $this->assertCount(1, $manager->employments);
            $this->assertEquals($startedAt, $manager->employments->first()->started_at->toDateTimeString());
        });
    }

    /** @test */
    public function a_basic_user_cannot_create_a_manager()
    {
        $this->actAs(Role::BASIC)
            ->from(route('managers.create'))
            ->post(route('managers.store', $this->validParams()))
            ->assertForbidden();
    }

    /** @test */
    public function a_guest_cannot_create_a_manager()
    {
        $this->from(route('managers.create'))
            ->post(route('managers.store', $this->validParams()))
            ->assertRedirect(route('login'));
    }

    /** @test */
    public function store_validates_using_a_form_request()
    {
        $this->assertActionUsesFormRequest(ManagersController::class, 'store', StoreRequest::class);
    }

    /**
     * @test
     * @dataProvider administrators
     */
    public function show_can_view_a_manager_profile($administrators)
    {
        $manager = Manager::factory()->create();

        $this->actAs($administrators)
            ->get(route('managers.show', $manager))
            ->assertViewIs('managers.show')
            ->assertViewHas('manager', $manager);
    }

    /** @test */
    public function a_basic_user_can_view_their_manager_profile()
    {
        $signedInUser = $this->actAs(Role::BASIC);
        $manager = Manager::factory()->create(['user_id' => $signedInUser->id]);

        $this->get(route('managers.show', $manager))
            ->assertOk();
    }

    /** @test */
    public function a_basic_user_cannot_view_another_users_manager_profile()
    {
        $otherUser = User::factory()->create();
        $manager = Manager::factory()->create(['user_id' => $otherUser->id]);

        $this->actAs(Role::BASIC)
            ->get(route('managers.show', $manager))
            ->assertForbidden();
    }

    /** @test */
    public function a_guest_cannot_view_a_manager_profile()
    {
        $manager = Manager::factory()->create();

        $this->get(route('managers.show', $manager))
            ->assertRedirect(route('login'));
    }

    /**
     * @test
     * @dataProvider administrators
     */
    public function edit_returns_a_view($administrators)
    {
        $manager = Manager::factory()->create();

        $this->actAs($administrators)
            ->get(route('managers.edit', $manager))
            ->assertViewIs('managers.edit')
            ->assertViewHas('manager', $manager);
    }

    /** @test */
    public function a_basic_user_cannot_view_the_form_for_editing_a_manager()
    {
        $manager = Manager::factory()->create();

        $this->actAs(Role::BASIC)
            ->get(route('managers.edit', $manager))
            ->assertForbidden();
    }

    /** @test */
    public function a_guest_cannot_view_the_form_for_editing_a_manager()
    {
        $manager = Manager::factory()->create();

        $this->get(route('managers.edit', $manager))
            ->assertRedirect(route('login'));
    }

    /**
     * @test
     * @dataProvider administrators
     */
    public function update_a_manage_and_redirects($administrators)
    {
        $manager = Manager::factory()->create();

        $this->actAs($administrators)
            ->from(route('managers.edit', $manager))
            ->put(route('managers.update', $manager), $this->validParams())
            ->assertRedirect(route('managers.index'));

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
        $manager = Manager::factory()->unemployed()->create();

        $this->actAs($administrators)
            ->from(route('managers.edit', $manager))
            ->put(route('managers.update', $manager), $this->validParams(['started_at' => $now]))
            ->assertRedirect(route('managers.index'));

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
        $manager = Manager::factory()->withFutureEmployment()->create();

        $this->actAs($administrators)
            ->from(route('managers.edit', $manager))
            ->put(route('managers.update', $manager), $this->validParams(['started_at' => $now]))
            ->assertRedirect(route('managers.index'));

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
        $manager = Manager::factory()->released()->create();

        $this->actAs($administrators)
            ->from(route('managers.edit', $manager))
            ->put(route('managers.update', $manager), $this->validParams(['started_at' => $now]))
            ->assertRedirect(route('managers.index'));

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
        $manager = Manager::factory()->available()->create();

        $this->actAs($administrators)
            ->from(route('managers.edit', $manager))
            ->put(route('managers.update', $manager), $this->validParams(['started_at' => $manager->employments()->first()->started_at->toDateTimeString()]))
            ->assertRedirect(route('managers.index'));

        tap($manager->fresh(), function ($manager) {
            $this->assertCount(1, $manager->employments);
        });
    }

    /** @test */
    public function a_basic_user_cannot_update_a_manager()
    {
        $manager = Manager::factory()->create();

        $this->actAs(Role::BASIC)
            ->from(route('managers.edit', $manager))
            ->put(route('managers.update', $manager), $this->validParams())
            ->assertForbidden();
    }

    /** @test */
    public function a_guest_cannot_update_a_manager()
    {
        $manager = Manager::factory()->create();

        $this->from(route('managers.edit', $manager))
            ->put(route('managers.update', $manager), $this->validParams())
            ->assertRedirect(route('login'));
    }

    /** @test */
    public function update_validates_using_a_form_request()
    {
        $this->assertActionUsesFormRequest(ManagersController::class, 'update', UpdateRequest::class);
    }

    /**
     * @test
     * @dataProvider administrators
     */
    public function delete_a_manager_and_redirects($administrators)
    {
        $manager = Manager::factory()->create();

        $this->actAs($administrators)
            ->delete(route('managers.destroy', $manager))
            ->assertRedirect(route('managers.index'));

        $this->assertSoftDeleted($manager);
    }

    /** @test */
    public function a_basic_user_cannot_delete_a_manager()
    {
        $manager = Manager::factory()->create();

        $this->actAs(Role::BASIC)
            ->delete(route('managers.destroy', $manager))
            ->assertForbidden();
    }

    /** @test */
    public function a_guest_cannot_delete_a_manager()
    {
        $manager = Manager::factory()->create();

        $this->delete(route('managers.destroy', $manager))
            ->assertRedirect(route('login'));
    }
}
