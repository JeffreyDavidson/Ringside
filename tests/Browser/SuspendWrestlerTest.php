<?php

namespace Tests\Browser;

use App\Models\User;
use App\Models\Wrestler;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Laravel\Dusk\Browser;
use Tests\Browser\Pages\Login;
use Tests\DuskTestCase;

class SuspendWrestlerTest extends DuskTestCase
{
    use DatabaseMigrations;

    /** @test */
    public function a_user_can_suspend_a_wrestler()
    {
        $user = User::factory()->superAdministrator()->create();
        $wrestler = Wrestler::factory()->bookable()->create();

        $this->browse(function (Browser $browser) use ($user, $wrestler) {
            $browser->visit(new Login)
                    ->fillInLoginForm($user->email, 'password')
                    ->visit(route('wrestlers.index'))
                    ->assertSee($wrestler->name);
        });
    }
}
