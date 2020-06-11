<?php

namespace Tests\Feature\Managers;

use App\Enums\Role;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Factories\EmploymentFactory;
use Tests\Factories\ManagerFactory;
use Tests\TestCase;

/**
 * @group managers
 * @group roster
 */
class UpdateManagerSuccessConditionsTest extends TestCase
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
     * @dataProvider adminRoles
     */
    public function administrators_can_view_the_form_for_editing_a_manager($adminRoles)
    {
        $this->actAs($adminRoles);
        $manager = ManagerFactory::new()->create();

        $response = $this->editRequest($manager);

        $response->assertViewIs('managers.edit');
        $this->assertTrue($response->data('manager')->is($manager));
    }

    /**
     * @test
     * @dataProvider adminRoles
     */
    public function administrators_can_update_a_manager($adminRoles)
    {
        $this->actAs($adminRoles);
        $manager = ManagerFactory::new()->create();

        $response = $this->updateRequest($manager, $this->validParams());

        $response->assertRedirect(route('managers.index'));
        tap($manager->fresh(), function ($manager) {
            $this->assertEquals('John', $manager->first_name);
            $this->assertEquals('Smith', $manager->last_name);
        });
    }

    /**
     * @test
     * @dataProvider adminRoles
     */
    public function a_manager_started_at_date_if_not_filled_can_be_changed_if_future_employment_started_at_is_in_future($adminRoles)
    {
        $this->actAs($adminRoles);
        $manager = ManagerFactory::new()
            ->employed(
                EmploymentFactory::new()->started(now()->addWeek()->toDateTimeString())
            )->create();

        $response = $this->updateRequest($manager, $this->validParams(['started_at' => '']));

        $response->assertSessionDoesntHaveErrors('started_at');
    }

    /**
     * @test
     * @dataProvider adminRoles
     */
    public function a_manager_started_at_date_if_filled_can_be_before_existing_employment_date_if_date_is_in_future($adminRoles)
    {
        $this->actAs($adminRoles);
        $manager = ManagerFactory::new()
            ->employed(
                EmploymentFactory::new()->started(now()->addWeek()->toDateTimeString())
            )
            ->create();

        $response = $this->updateRequest($manager, $this->validParams([
            'started_at' => now()->addDays(2)->toDateTimeString(),
        ]));

        $response->assertSessionDoesntHaveErrors('started_at');
    }

    public function adminRoles()
    {
        return [
            [Role::ADMINISTRATOR],
            [Role::SUPER_ADMINISTRATOR],
        ];
    }
}
