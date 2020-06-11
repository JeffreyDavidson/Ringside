<?php

namespace Tests\Feature\Managers;

use App\Enums\Role;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Factories\EmploymentFactory;
use Tests\Factories\ManagerFactory;
use Tests\TestCase;

/**
 * @group managers
 * @group roster
 */
class UpdateManagerFailureConditionsTest extends TestCase
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
    public function a_basic_user_cannot_view_the_form_for_editing_a_manager()
    {
        $this->actAs(Role::BASIC);
        $manager = ManagerFactory::new()->create();

        $response = $this->editRequest($manager);

        $response->assertForbidden();
    }

    /** @test */
    public function a_basic_user_cannot_update_a_manager()
    {
        $this->actAs(Role::BASIC);
        $manager = ManagerFactory::new()->create();

        $response = $this->updateRequest($manager, $this->validParams());

        $response->assertForbidden();
    }

    /** @test */
    public function a_guest_cannot_view_the_form_for_editing_a_manager()
    {
        $manager = ManagerFactory::new()->create();

        $response = $this->editRequest($manager);

        $response->assertRedirect(route('login'));
    }

    /** @test */
    public function a_guest_cannot_update_a_manager()
    {
        $manager = ManagerFactory::new()->create();

        $response = $this->updateRequest($manager, $this->validParams());

        $response->assertRedirect(route('login'));
    }

    /**
     * @test
     * @dataProvider adminRoles
     */
    public function a_manager_started_at_date_is_required_if_employment_start_date_is_in_past($adminRoles)
    {
        $this->actAs($adminRoles);
        $manager = ManagerFactory::new()->employed(
                EmploymentFactory::new()->started(now()->subWeek()->toDateTimeString())
        )->create();

        $response = $this->updateRequest($manager, $this->validParams(['started_at' => '']));

        $response->assertRedirect(route('managers.edit', $manager));
        $response->assertSessionHasErrors('started_at');
        tap($manager->fresh(), function ($manager) {
            $this->assertNotNull($manager->currentEmployment->started_at);
        });
    }

    /**
     * @test
     * @dataProvider adminRoles
     */
    public function a_manager_started_at_date_if_filled_cannot_be_after_existing_employment_date_if_date_has_past($adminRoles)
    {
        $this->actAs($adminRoles);
        $manager = ManagerFactory::new()
            ->employed(
                EmploymentFactory::new()->started(Carbon::yesterday()->toDateTimeString())
            )
            ->create();

        $response = $this->updateRequest($manager, $this->validParams([
            'started_at' => Carbon::tomorrow()->toDateTimeString(),
        ]));

        $response->assertRedirect(route('managers.edit', $manager));
        $response->assertSessionHasErrors('started_at');
    }

    public function adminRoles()
    {
        return [
            [Role::ADMINISTRATOR],
            [Role::SUPER_ADMINISTRATOR],
        ];
    }
}
