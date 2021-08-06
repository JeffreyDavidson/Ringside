<?php

namespace Tests\Browser;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Laravel\Dusk\Browser;
use Tests\Browser\Pages\Login;
use Tests\DuskTestCase;

class LoginTest extends DuskTestCase
{
    use DatabaseMigrations;

    /**
     * @test
     */
    public function a_user_can_login()
    {
        $user = User::factory()->create(['email' => 'smith@example.com', 'password' => 'testpass123']);

        $this->browse(function (Browser $browser) use ($user) {
            $browser->visit(new Login)
                    ->fillInLoginForm($user->email, 'password')
                    ->assertAuthenticatedAs($user);
        });
    }
}
