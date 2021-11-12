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
class ManagerControllerUpdateMethodTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @test
     */
    public function edit_returns_a_view()
    {
        $manager = Manager::factory()->create();

        $this
            ->actAs(Role::ADMINISTRATOR)
            ->get(action([ManagersController::class, 'edit'], $manager))
            ->assertViewIs('managers.edit')
            ->assertViewHas('manager', $manager);
    }

    /**
     * @test
     */
    public function a_basic_user_cannot_view_the_form_for_editing_a_manager()
    {
        $manager = Manager::factory()->create();

        $this
            ->actAs(Role::BASIC)
            ->get(action([ManagersController::class, 'edit'], $manager))
            ->assertForbidden();
    }

    /**
     * @test
     */
    public function a_guest_cannot_view_the_form_for_editing_a_manager()
    {
        $manager = Manager::factory()->create();

        $this
            ->get(action([ManagersController::class, 'edit'], $manager))
            ->assertRedirect(route('login'));
    }

    /**
     * @test
     */
    public function update_a_manager_and_redirects()
    {
        $manager = Manager::factory()->create(['first_name' => 'John', 'last_name' => 'Smith']);

        $this
            ->actAs(Role::ADMINISTRATOR)
            ->from(action([ManagersController::class, 'edit'], $manager))
            ->put(
                action([ManagersController::class, 'update'], $manager),
                ManagerRequestDataFactory::new()->withManager($manager)->create([
                    'first_name' => 'Paul',
                    'last_name' => 'Williams',
                ])
            )
            ->assertRedirect(route('managers.index'));

        tap($manager->fresh(), function ($manager) {
            $this->assertEquals('Paul', $manager->first_name);
            $this->assertEquals('Williams', $manager->last_name);
        });
    }

    /**
     * @test
     */
    public function update_can_employ_an_unemployed_manager_when_started_at_is_filled()
    {
        $now = now()->toDateTimeString();
        $manager = Manager::factory()->unemployed()->create();

        $this->assertCount(0, $manager->employments);

        $this
            ->actAs(Role::ADMINISTRATOR)
            ->from(action([ManagersController::class, 'edit'], $manager))
            ->put(
                action([ManagersController::class, 'update'], $manager),
                ManagerRequestDataFactory::new()->withManager($manager)->create(['started_at' => $now])
            )
            ->assertRedirect(action([ManagersController::class, 'index']));

        tap($manager->fresh(), function ($manager) use ($now) {
            $this->assertCount(1, $manager->employments);
            $this->assertEquals($now, $manager->employments->first()->started_at->toDateTimeString());
        });
    }

    /**
     * @test
     */
    public function update_can_employ_a_future_employed_manager_when_started_at_is_filled()
    {
        $now = now()->toDateTimeString();
        $manager = Manager::factory()->withFutureEmployment()->create();

        $this->assertTrue($manager->employments()->first()->started_at->isFuture());

        $this
            ->actAs(Role::ADMINISTRATOR)
            ->from(action([ManagersController::class, 'edit'], $manager))
            ->put(
                action([ManagersController::class, 'update'], $manager),
                ManagerRequestDataFactory::new()->withManager($manager)->create(['started_at' => $now])
            )
            ->assertRedirect(action([ManagersController::class, 'index']));

        tap($manager->fresh(), function ($manager) use ($now) {
            $this->assertCount(1, $manager->employments);
            $this->assertEquals($now, $manager->employments()->first()->started_at->toDateTimeString());
        });
    }

    /**
     * @test
     */
    public function update_cannot_reemploy_a_released_manager()
    {
        $manager = Manager::factory()->released()->create();
        $startDate = $manager->employments->last()->started_at->toDateTimeString();

        $this->assertCount(1, $manager->employments);

        $this
            ->actAs(Role::ADMINISTRATOR)
            ->from(action([ManagersController::class, 'edit'], $manager))
            ->put(
                action([ManagersController::class, 'update'], $manager),
                ManagerRequestDataFactory::new()->withManager($manager)->create([
                    'started_at' => now()->toDateTimeString(),
                ])
            )
            ->assertSessionHasErrors(['started_at']);

        tap($manager->fresh(), function ($manager) use ($startDate) {
            $this->assertSame($startDate, $manager->employments->last()->started_at->toDateTimeString());
        });
    }

    /**
     * @test
     */
    public function updating_cannot_employ_an_available_manager_when_started_at_is_filled()
    {
        $manager = Manager::factory()->available()->create();
        $startDate = $manager->employments()->first()->started_at->toDateTimeString();

        $this
            ->actAs(Role::ADMINISTRATOR)
            ->from(action([ManagersController::class, 'edit'], $manager))
            ->put(
                action([ManagersController::class, 'update'], $manager),
                ManagerRequestDataFactory::new()->withManager($manager)->create([
                    'started_at' => now()->toDateTimeString(),
                ])
            )
            ->assertSessionHasErrors(['started_at']);

        tap($manager->fresh(), function ($manager) use ($startDate) {
            $this->assertSame($startDate, $manager->employments->first()->started_at->toDateTimeString());
        });
    }

    /**
     * @test
     */
    public function a_basic_user_cannot_update_a_manager()
    {
        $manager = Manager::factory()->create();

        $this
            ->actAs(Role::BASIC)
            ->from(action([ManagersController::class, 'edit'], $manager))
            ->put(
                action([ManagersController::class, 'update'], $manager),
                ManagerRequestDataFactory::new()->withManager($manager)->create()
            )
            ->assertForbidden();
    }

    /**
     * @test
     */
    public function a_guest_cannot_update_a_manager()
    {
        $manager = Manager::factory()->create();

        $this
            ->from(action([ManagersController::class, 'edit'], $manager))
            ->put(
                action([ManagersController::class, 'update'], $manager),
                ManagerRequestDataFactory::new()->withManager($manager)->create()
            )
            ->assertRedirect(route('login'));
    }
}
