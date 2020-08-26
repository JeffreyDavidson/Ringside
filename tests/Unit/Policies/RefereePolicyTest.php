<?php

namespace Tests\Unit\Policies;

use Tests\TestCase;
use App\Models\Referee;
use App\Policies\RefereePolicy;
use Tests\Factories\UserFactory;
use Tests\Factories\RefereeFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * @group referees
 * @group roster
 * @group policies
 */
class RefereePolicyTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->policy = new RefereePolicy;
        Referee::unsetEventDispatcher();
    }

    /** @test */
    public function a_basic_user_cannot_create_a_referee()
    {
        $user = UserFactory::new()->basicUser()->create();

        $this->assertFalse($this->policy->create($user));
    }

    /** @test */
    public function an_administrator_can_create_a_referee()
    {
        $user = UserFactory::new()->administrator()->create();

        $this->assertTrue($this->policy->create($user));
    }

    /** @test */
    public function a_super_administrator_can_create_a_referee()
    {
        $user = UserFactory::new()->superAdministrator()->create();

        $this->assertTrue($this->policy->create($user));
    }

    /** @test */
    public function a_basic_user_cannot_update_a_referee()
    {
        $user = UserFactory::new()->basicUser()->create();

        $this->assertFalse($this->policy->update($user));
    }

    /** @test */
    public function an_administrator_can_update_a_referee()
    {
        $user = UserFactory::new()->administrator()->create();

        $this->assertTrue($this->policy->update($user));
    }

    /** @test */
    public function a_super_administrator_can_update_a_referee()
    {
        $user = UserFactory::new()->superAdministrator()->create();

        $this->assertTrue($this->policy->update($user));
    }

    /** @test */
    public function a_basic_user_cannot_delete_a_referee()
    {
        $user = UserFactory::new()->basicUser()->create();

        $this->assertFalse($this->policy->delete($user));
    }

    /** @test */
    public function an_administrator_can_delete_a_referee()
    {
        $user = UserFactory::new()->administrator()->create();

        $this->assertTrue($this->policy->delete($user));
    }

    /** @test */
    public function a_super_administrator_can_delete_a_referee()
    {
        $user = UserFactory::new()->superAdministrator()->create();

        $this->assertTrue($this->policy->delete($user));
    }

    /** @test */
    public function a_basic_user_cannot_restore_a_referee()
    {
        $user = UserFactory::new()->basicUser()->create();

        $this->assertFalse($this->policy->restore($user));
    }

    /** @test */
    public function an_administrator_can_restore_a_referee()
    {
        $user = UserFactory::new()->administrator()->create();

        $this->assertTrue($this->policy->restore($user));
    }

    /** @test */
    public function a_super_administrator_can_restore_a_referee()
    {
        $user = UserFactory::new()->superAdministrator()->create();

        $this->assertTrue($this->policy->restore($user));
    }

    /** @test */
    public function a_basic_user_cannot_view_list_of_referees()
    {
        $user = UserFactory::new()->basicUser()->create();

        $this->assertFalse($this->policy->viewList($user));
    }

    /** @test */
    public function an_administrator_can_view_list_of_referees()
    {
        $user = UserFactory::new()->administrator()->create();

        $this->assertTrue($this->policy->viewList($user));
    }

    /** @test */
    public function a_super_administrator_can_view_list_of_referees()
    {
        $user = UserFactory::new()->superAdministrator()->create();

        $this->assertTrue($this->policy->viewList($user));
    }

    /** @test */
    public function a_basic_user_cannot_employ_a_referee()
    {
        $user = UserFactory::new()->basicUser()->create();

        $this->assertFalse($this->policy->employ($user));
    }

    /** @test */
    public function an_administrator_can_employ_a_referee()
    {
        $user = UserFactory::new()->administrator()->create();

        $this->assertTrue($this->policy->employ($user));
    }

    /** @test */
    public function a_super_administrator_can_employ_a_referee()
    {
        $user = UserFactory::new()->superAdministrator()->create();

        $this->assertTrue($this->policy->employ($user));
    }

    /** @test */
    public function a_basic_user_cannot_injure_a_referee()
    {
        $user = UserFactory::new()->basicUser()->create();

        $this->assertFalse($this->policy->injure($user));
    }

    /** @test */
    public function an_administrator_can_injure_a_referee()
    {
        $user = UserFactory::new()->administrator()->create();

        $this->assertTrue($this->policy->injure($user));
    }

    /** @test */
    public function a_super_administrator_can_injure_a_referee()
    {
        $user = UserFactory::new()->superAdministrator()->create();

        $this->assertTrue($this->policy->injure($user));
    }

    /** @test */
    public function a_basic_user_cannot_clear_a_referee_from_an_injury()
    {
        $user = UserFactory::new()->basicUser()->create();

        $this->assertFalse($this->policy->clearFromInjury($user));
    }

    /** @test */
    public function an_administrator_can_clear_a_referee_from_injury()
    {
        $user = UserFactory::new()->administrator()->create();

        $this->assertTrue($this->policy->clearFromInjury($user));
    }

    /** @test */
    public function a_super_administrator_can_clear_a_referee_from_injury()
    {
        $user = UserFactory::new()->superAdministrator()->create();

        $this->assertTrue($this->policy->clearFromInjury($user));
    }

    /** @test */
    public function a_basic_user_cannot_suspend_a_referee()
    {
        $user = UserFactory::new()->basicUser()->create();

        $this->assertFalse($this->policy->suspend($user));
    }

    /** @test */
    public function an_administrator_can_suspend_a_referee()
    {
        $user = UserFactory::new()->administrator()->create();
        $referee = RefereeFactory::new()->create();

        $this->assertTrue($this->policy->suspend($user));
    }

    /** @test */
    public function a_super_administrator_can_suspend_a_referee()
    {
        $user = UserFactory::new()->superAdministrator()->create();

        $this->assertTrue($this->policy->suspend($user));
    }

    /** @test */
    public function a_basic_user_cannot_reinstate_a_referee()
    {
        $user = UserFactory::new()->basicUser()->create();

        $this->assertFalse($this->policy->reinstate($user));
    }

    /** @test */
    public function an_administrator_can_reinstate_a_referee()
    {
        $user = UserFactory::new()->administrator()->create();

        $this->assertTrue($this->policy->reinstate($user));
    }

    /** @test */
    public function a_super_administrator_can_reinstate_a_referee()
    {
        $user = UserFactory::new()->superAdministrator()->create();

        $this->assertTrue($this->policy->reinstate($user));
    }

    /** @test */
    public function a_basic_user_cannot_retire_a_referee()
    {
        $user = UserFactory::new()->basicUser()->create();

        $this->assertFalse($this->policy->retire($user));
    }

    /** @test */
    public function an_administrator_can_retire_a_referee()
    {
        $user = UserFactory::new()->administrator()->create();

        $this->assertTrue($this->policy->retire($user));
    }

    /** @test */
    public function a_super_administrator_can_retire_a_referee()
    {
        $user = UserFactory::new()->superAdministrator()->create();

        $this->assertTrue($this->policy->retire($user));
    }

    /** @test */
    public function a_basic_user_cannot_unretire_a_referee()
    {
        $user = UserFactory::new()->basicUser()->create();

        $this->assertFalse($this->policy->unretire($user));
    }

    /** @test */
    public function an_administrator_can_unretire_a_referee()
    {
        $user = UserFactory::new()->administrator()->create();

        $this->assertTrue($this->policy->unretire($user));
    }

    /** @test */
    public function a_super_administrator_can_unretire_a_referee()
    {
        $user = UserFactory::new()->superAdministrator()->create();

        $this->assertTrue($this->policy->unretire($user));
    }

    /** @test */
    public function a_basic_user_cannot_view_a_referee_profile_not_owned_by_user()
    {
        $user = UserFactory::new()->basicUser()->create();

        $this->assertFalse($this->policy->view($user));
    }

    /** @test */
    public function an_administrator_can_view_a_referee_profile()
    {
        $user = UserFactory::new()->administrator()->create();

        $this->assertTrue($this->policy->view($user));
    }

    /** @test */
    public function a_super_administrator_can_view_a_referee_profile()
    {
        $user = UserFactory::new()->superAdministrator()->create();

        $this->assertTrue($this->policy->view($user));
    }
}
