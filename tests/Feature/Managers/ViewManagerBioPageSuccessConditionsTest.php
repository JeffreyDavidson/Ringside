<?php

namespace Tests\Feature\Managers;

use App\Enums\Role;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Factories\ManagerFactory;
use Tests\TestCase;

/**
 * @group managers
 * @group roster
 */
class ViewManagerBioPageSuccessConditionsTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @test
     * @dataProvider adminRoles
     */
    public function administrators_can_view_a_manager_profile($adminRoles)
    {
        $this->actAs($adminRoles);
        $manager = ManagerFactory::new()->create();

        $response = $this->showRequest($manager);

        $response->assertViewIs('managers.show');
        $this->assertTrue($response->data('manager')->is($manager));
    }

    /** @test */
    public function a_basic_user_can_view_their_manager_profile()
    {
        $signedInUser = $this->actAs(Role::BASIC);
        $manager = ManagerFactory::new()->create(['user_id' => $signedInUser->id]);

        $response = $this->showRequest($manager);

        $response->assertOk();
    }

    public function adminRoles()
    {
        return [
            [Role::ADMINISTRATOR],
            [Role::SUPER_ADMINISTRATOR],
        ];
    }
}
