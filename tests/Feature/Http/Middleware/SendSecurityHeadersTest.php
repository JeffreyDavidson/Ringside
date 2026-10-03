<?php

declare(strict_types=1);

use App\Enums\Users\UserStatus;
use App\Models\Users\User;
use Illuminate\Support\Facades\Route;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;

test('pages send the referrer, permissions and content type policies once', function () {
    // Act
    $response = get(route('login'));

    // Assert
    $response->assertOk();
    expect($response->headers->all('Referrer-Policy'))->toBe(['strict-origin-when-cross-origin'])
        ->and($response->headers->all('X-Content-Type-Options'))->toBe(['nosniff'])
        ->and($response->headers->all('Permissions-Policy'))->toHaveCount(1)
        ->and($response->headers->get('Permissions-Policy'))
        ->toContain('camera=()')
        ->toContain('microphone=()')
        ->toContain('geolocation=()')
        ->toContain('payment=()')
        ->toContain('usb=()');
});

test('responses short-circuited by later web middleware still send the headers', function () {
    // Arrange
    $user = User::factory()->create(['status' => UserStatus::Active]);
    actingAs($user);
    $user->update(['status' => UserStatus::Inactive]);

    // Act
    $response = get(route('dashboard'));

    // Assert
    $response
        ->assertRedirect(route('login'))
        ->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin')
        ->assertHeader('X-Content-Type-Options', 'nosniff');
});

test('headers a route already sets are kept and not duplicated', function () {
    // Arrange
    Route::middleware('web')->get('/security-headers-probe', fn () => response('probe')
        ->header('Referrer-Policy', 'no-referrer')
        ->header('X-Content-Type-Options', 'nosniff'));

    // Act
    $response = get('/security-headers-probe');

    // Assert
    expect($response->headers->all('Referrer-Policy'))->toBe(['no-referrer'])
        ->and($response->headers->all('X-Content-Type-Options'))->toBe(['nosniff'])
        ->and($response->headers->all('Permissions-Policy'))->toHaveCount(1);
});

test('routes outside the web group are left alone', function () {
    // Act
    $response = get('/up');

    // Assert
    $response
        ->assertOk()
        ->assertHeaderMissing('Referrer-Policy')
        ->assertHeaderMissing('Permissions-Policy');
});
