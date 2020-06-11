<?php

namespace Tests\Feature\Wrestlers;

use App\Enums\Role;
use Tests\TestCase;
use Tests\Factories\UserFactory;
use Tests\Factories\WrestlerFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * @group wrestlers
 * @group roster
 */
class ViewWrestlerBioPageTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function an_administrator_can_view_a_wrestler_profile()
    {
        $this->actAs(Role::ADMINISTRATOR);
        $wrestler = WrestlerFactory::new()->create();

        $response = $this->showRequest($wrestler);

        $response->assertViewIs('wrestlers.show');
        $this->assertTrue($response->data('wrestler')->is($wrestler));
    }

    /** @test */
    public function a_basic_user_can_view_their_wrestler_profile()
    {
        $signedInUser = $this->actAs(Role::BASIC);
        $wrestler = WrestlerFactory::new()->create(['user_id' => $signedInUser->id]);

        $response = $this->showRequest($wrestler);

        $response->assertOk();
    }

    /** @test */
    public function a_basic_user_cannot_view_another_users_wrestler_profile()
    {
        $this->actAs(Role::BASIC);
        $otherUser = UserFactory::new()->create();
        $wrestler = WrestlerFactory::new()->create(['user_id' => $otherUser->id]);

        $response = $this->showRequest($wrestler);

        $response->assertForbidden();
    }

    /** @test */
    public function a_guest_cannot_view_a_wrestler_profile()
    {
        $wrestler = WrestlerFactory::new()->create();

        $response = $this->showRequest($wrestler);

        $response->assertRedirect(route('login'));
    }
}
