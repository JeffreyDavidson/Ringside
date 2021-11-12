<?php

namespace Tests\Feature\Http\Controllers\Managers;

use App\Enums\Role;
use App\Http\Controllers\Managers\ManagersController;
use App\Models\Manager;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Factories\ManagerRequestDataFactory;
use Tests\TestCase;

/**
 * @group managers
 * @group feature-managers
 * @group roster
 * @group feature-roster
 */
class ManagerControllerStoreMethodTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @test
     */
    public function create_returns_a_view()
    {
        $this
            ->actAs(Role::administrator())
            ->get(action([ManagersController::class, 'create']))
            ->assertViewIs('managers.create')
            ->assertViewHas('manager', new Manager);
    }

    /**
     * @test
     */
    public function a_basic_user_cannot_view_the_form_for_creating_a_manager()
    {
        $this
            ->actAs(Role::basic())
            ->get(action([ManagersController::class, 'create']))
            ->assertForbidden();
    }

    /**
     * @test
     */
    public function a_guest_cannot_view_the_form_for_creating_a_manager()
    {
        $this
            ->get(action([ManagersController::class, 'create']))
            ->assertRedirect(route('login'));
    }

    /**
     * @test
     */
    public function store_creates_a_manager_and_redirects()
    {
        $this
            ->actAs(Role::administrator())
            ->from(action([ManagersController::class, 'create']))
            ->post(action([ManagersController::class, 'store'], ManagerRequestDataFactory::new()->create([
                'first_name' => 'John',
                'last_name' => 'Smith',
                'started_at' => null,
            ])))
            ->assertRedirect(action([ManagersController::class, 'index']));

        tap(Manager::first(), function ($manager) {
            $this->assertEquals('John', $manager->first_name);
            $this->assertEquals('Smith', $manager->last_name);
        });
    }

    /**
     * @test
     */
    public function an_employment_is_not_created_for_the_manager_if_started_at_is_filled_in_request()
    {
        $this
            ->actAs(Role::administrator())
            ->from(action([ManagersController::class, 'create']))
            ->post(
                action([ManagersController::class, 'store']),
                ManagerRequestDataFactory::new()->create(['started_at' => null])
            );

        tap(Manager::first(), function ($manager) {
            $this->assertCount(0, $manager->employments);
        });
    }

    /**
     * @test
     */
    public function an_employment_is_created_for_the_manager_if_started_at_is_filled_in_request()
    {
        $startedAt = now()->toDateTimeString();

        $this
            ->actAs(Role::administrator())
            ->from(action([ManagersController::class, 'create']))
            ->post(
                action([ManagersController::class, 'store']),
                ManagerRequestDataFactory::new()->create(['started_at' => $startedAt])
            );

        tap(Manager::first(), function ($manager) use ($startedAt) {
            $this->assertCount(1, $manager->employments);
            $this->assertEquals($startedAt, $manager->employments->first()->started_at->toDateTimeString());
        });
    }

    /**
     * @test
     */
    public function a_basic_user_cannot_create_a_manager()
    {
        $this
            ->actAs(Role::basic())
            ->from(action([ManagersController::class, 'create']))
            ->post(action([ManagersController::class, 'store'], ManagerRequestDataFactory::new()->create()))
            ->assertForbidden();
    }

    /**
     * @test
     */
    public function a_guest_cannot_create_a_manager()
    {
        $this
            ->from(action([ManagersController::class, 'create']))
            ->post(action([ManagersController::class, 'store'], ManagerRequestDataFactory::new()->create()))
            ->assertRedirect(route('login'));
    }
}
