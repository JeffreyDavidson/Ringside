<?php

declare(strict_types=1);

use App\Enums\Users\UserStatus;
use App\Models\Users\User;
use Illuminate\Support\Facades\Route;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;

test('pages send the referrer, permissions and content type policies once', function (): void {
    // Arrange
    $url = route('login');

    // Act
    $response = get($url);

    // Assert
    $response
        ->assertOk();

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

test('responses short-circuited by later web middleware still send the headers', function (): void {
    // Arrange
    $user = User::factory()
        ->create(['status' => UserStatus::Active]);

    actingAs($user);
    $user->update(['status' => UserStatus::Inactive]);

    // Act
    $response = get(route('dashboard'));

    // Assert
    $response
        ->assertRedirect(route('login'))
        ->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin')
        ->assertHeader('X-Content-Type-Options', 'nosniff')
        ->assertHeaderContains('Permissions-Policy', 'camera=()')
        ->assertHeaderContains('Permissions-Policy', 'microphone=()')
        ->assertHeaderContains('Permissions-Policy', 'geolocation=()')
        ->assertHeaderContains('Permissions-Policy', 'payment=()')
        ->assertHeaderContains('Permissions-Policy', 'usb=()');
});

test('downstream status body cookie and headers are preserved without duplicating security policies', function (): void {
    // Arrange
    Route::name('middleware.securityHeadersProbe')
        ->middleware('web')
        ->get('/security-headers-probe', fn () => response('Security header preservation probe', 201)
            ->cookie('response_marker', 'route-cookie-value', 10)
            ->header('X-Route-Marker', 'route-header-value')
            ->header('Referrer-Policy', 'no-referrer')
            ->header('X-Content-Type-Options', 'nosniff')
            ->header('Permissions-Policy', 'camera=(self)')
            ->header('Strict-Transport-Security', 'max-age=3600'));
    $url = str_replace('http://', 'https://', route('middleware.securityHeadersProbe'));

    // Act
    $response = get($url);

    // Assert
    $response
        ->assertCreated()
        ->assertContent('Security header preservation probe')
        ->assertCookie('response_marker', 'route-cookie-value')
        ->assertHeader('X-Route-Marker', 'route-header-value');

    expect($response->headers->all('Referrer-Policy'))->toBe(['no-referrer'])
        ->and($response->headers->all('X-Content-Type-Options'))->toBe(['nosniff'])
        ->and($response->headers->all('Permissions-Policy'))->toBe(['camera=(self)'])
        ->and($response->headers->all('Strict-Transport-Security'))->toBe(['max-age=3600']);
});

test('routes outside the web group are left alone', function (): void {
    // Arrange
    $url = '/up';

    // Act
    $response = get($url);

    // Assert
    $response
        ->assertOk()
        ->assertHeaderMissing('Referrer-Policy')
        ->assertHeaderMissing('Permissions-Policy')
        ->assertHeaderMissing('X-Content-Type-Options');
});

test('secure requests are told to use https for six months', function (): void {
    // Arrange
    $url = str_replace('http://', 'https://', route('login'));

    // Act
    $response = get($url);

    // Assert
    $response
        ->assertOk();

    expect($response->headers->all('Strict-Transport-Security'))->toBe(['max-age=15552000']);
});

test('plain http requests do not get the https only header', function (): void {
    // Arrange
    $url = str_replace('https://', 'http://', route('login'));

    // Act
    $response = get($url);

    // Assert
    $response
        ->assertOk()
        ->assertHeaderMissing('Strict-Transport-Security');
});
