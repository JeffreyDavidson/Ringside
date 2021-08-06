<?php

namespace Tests\Browser;

use App\Models\User;
use App\Models\Wrestler;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Laravel\Dusk\Browser;
use Tests\Browser\Pages\Login;
use Tests\DuskTestCase;

class ClearWrestlerInjuryTest extends DuskTestCase
{
    use DatabaseMigrations;

    /**
     * @test
     */
    public function testExample()
    {
        $user = User::factory()->superAdministrator()->create();
        $wrestler = Wrestler::factory()->injured()->create();

        $this->browse(function (Browser $browser) {
            $browser->visit(new Login)
                    ->fillInLoginForm($user->email, 'password')
                    ->click('Wrestlers')
                    ->assertPathIs('/roster/wrestlers')
                    ->assertSee($wrestler->name)
                    ->click();
        });
    }
}
