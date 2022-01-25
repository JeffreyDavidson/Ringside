<?php

namespace Tests\Feature\Http\Controllers\Auth;

use App\Models\User;
use Tests\TestCase;

class PasswordConfirmationTest extends TestCase
{
    /**
     * @test
     */
    public function confirm_password_screen_can_be_rendered()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/confirm-password');

        $response->assertStatus(200);
    }

    /**
     * @test
     */
    public function password_can_be_confirmed()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/confirm-password', [
            'password' => 'secret',
        ]);

        $response->assertRedirect();
        $response->assertSessionHasNoErrors();
    }

    /**
     * @test
     */
    public function password_is_not_confirmed_with_invalid_password()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/confirm-password', [
            'password' => 'wrong-password',
        ]);

        $response->assertSessionHasErrors();
    }
}
