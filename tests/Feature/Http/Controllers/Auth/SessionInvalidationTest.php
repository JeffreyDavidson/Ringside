<?php

declare(strict_types=1);

use App\Enums\Users\UserStatus;
use App\Models\Users\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

use function Pest\Laravel\assertAuthenticated;
use function Pest\Laravel\assertGuest;
use function Pest\Laravel\get;
use function Pest\Laravel\post;

test('a session ends once the account password changes', function (): void {
    // Arrange
    $user = User::factory()->administrator()->create(['email' => 'promoter@example.com', 'status' => UserStatus::Active]);
    post(route('login'), ['email' => 'promoter@example.com', 'password' => 'secret']);
    get(route('promotions.index'))->assertSuccessful();
    DB::table('users')->where('id', $user->id)->update(['password' => Hash::make('a-brand-new-password')]);
    auth()->forgetGuards();

    // Act
    $response = get(route('promotions.index'));

    // Assert
    $response->assertRedirect(route('login'));
    assertGuest();
});

test('a session continues while the account password is unchanged', function (): void {
    // Arrange
    User::factory()->administrator()->create(['email' => 'promoter@example.com', 'status' => UserStatus::Active]);
    post(route('login'), ['email' => 'promoter@example.com', 'password' => 'secret']);
    get(route('promotions.index'))->assertSuccessful();

    // Act
    $response = get(route('promotions.index'));

    // Assert
    $response->assertSuccessful();
    assertAuthenticated();
});
