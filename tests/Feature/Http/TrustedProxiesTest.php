<?php

declare(strict_types=1);

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Testing\TestResponse;
use Symfony\Component\HttpFoundation\IpUtils;
use Symfony\Component\HttpFoundation\Response;

use function Pest\Laravel\assertDatabaseCount;
use function Pest\Laravel\withServerVariables;

beforeEach(function (): void {
    Route::name('middleware.trustedProxyProbe')
        ->get('/trusted-proxy-probe', fn (Request $request): array => [
            'ip' => $request->ip(),
            'secure' => $request->isSecure(),
            'host' => $request->getHost(),
            'port' => $request->getPort(),
            'reset_link' => route('password.request'),
        ]);
});

/**
 * @param  array<string, string>  $headers
 * @return TestResponse<Response>
 */
function probeFrom(string $peer, array $headers = []): TestResponse
{
    $path = route('middleware.trustedProxyProbe', absolute: false);

    return withServerVariables(['REMOTE_ADDR' => $peer])
        ->withHeaders($headers)
        ->getJson("http://origin.example.test{$path}");
}

test('the real client address is used when the request comes through Cloudflare', function (string $cloudflarePeer): void {
    // Arrange
    $headers = ['X-Forwarded-For' => '203.0.113.9'];

    // Act
    $response = probeFrom($cloudflarePeer, $headers);

    // Assert
    $response
        ->assertOk()
        ->assertJsonPath('ip', '203.0.113.9');
})->with([
    'IPv4 edge' => '173.245.48.10',
    'IPv6 edge' => '2400:cb00::1',
]);

test('a spoofed forwarded address in front of the real one is ignored', function (): void {
    // Arrange
    $headers = ['X-Forwarded-For' => '1.2.3.4, 203.0.113.9'];

    // Act
    $response = probeFrom('173.245.48.10', $headers);

    // Assert
    $response
        ->assertOk()
        ->assertJsonPath('ip', '203.0.113.9');
});

test('forwarded headers from a peer that is not Cloudflare are ignored', function (): void {
    // Arrange
    $headers = [
        'X-Forwarded-For' => '203.0.113.9',
        'X-Forwarded-Proto' => 'https',
    ];

    // Act
    $response = probeFrom('198.51.100.7', $headers);

    // Assert
    $response
        ->assertOk()
        ->assertJsonPath('ip', '198.51.100.7')
        ->assertJsonPath('secure', false);
});

test('the forwarded protocol is trusted from Cloudflare', function (): void {
    // Arrange
    $headers = ['X-Forwarded-Proto' => 'https'];

    // Act
    $response = probeFrom('173.245.48.10', $headers);

    // Assert
    $response
        ->assertOk()
        ->assertJsonPath('secure', true);
});

test('a forwarded host header is never trusted, even from Cloudflare', function (): void {
    // Arrange
    $headers = ['X-Forwarded-Host' => 'evil.example'];

    // Act
    $response = probeFrom('173.245.48.10', $headers);

    // Assert
    $response
        ->assertOk()
        ->assertJsonPath('host', 'origin.example.test')
        ->assertJsonPath('reset_link', 'http://origin.example.test/forgot-password');
});

test('a forwarded port header is never trusted, even from Cloudflare', function (): void {
    // Arrange
    $headers = [
        'X-Forwarded-Proto' => 'https',
        'X-Forwarded-Port' => '8443',
    ];

    // Act
    $response = probeFrom('173.245.48.10', $headers);

    // Assert
    $response
        ->assertOk()
        ->assertJsonPath('secure', true)
        ->assertJsonPath('port', 443)
        ->assertJsonPath('reset_link', 'https://origin.example.test/forgot-password');
});

test('a visitor cannot bypass their exhausted registration quota through Cloudflare', function (): void {
    // Arrange
    $url = route('register');

    foreach (range(1, 6) as $attempt) {
        $baseline = withServerVariables(['REMOTE_ADDR' => '173.245.48.10'])
            ->from($url)
            ->withHeaders(['X-Forwarded-For' => '203.0.113.9'])
            ->post($url, []);
        $baseline
            ->assertRedirect($url)
            ->assertSessionHasErrors(['first_name', 'last_name', 'email', 'password']);
    }

    // Act
    $response = withServerVariables(['REMOTE_ADDR' => '173.245.48.10'])
        ->withHeaders(['X-Forwarded-For' => '203.0.113.9'])
        ->post($url, []);

    // Assert
    $response
        ->assertTooManyRequests();

    assertDatabaseCount('users', 0);
});

test('another visitor behind the same Cloudflare address has an independent registration quota', function (): void {
    // Arrange
    $url = route('register');

    foreach (range(1, 6) as $attempt) {
        $baseline = withServerVariables(['REMOTE_ADDR' => '173.245.48.10'])
            ->from($url)
            ->withHeaders(['X-Forwarded-For' => '203.0.113.9'])
            ->post($url, []);
        $baseline
            ->assertRedirect($url)
            ->assertSessionHasErrors(['first_name', 'last_name', 'email', 'password']);
    }

    $exhaustedVisitor = withServerVariables(['REMOTE_ADDR' => '173.245.48.10'])
        ->withHeaders(['X-Forwarded-For' => '203.0.113.9'])
        ->post($url, []);
    $exhaustedVisitor
        ->assertTooManyRequests();

    // Act
    $response = withServerVariables(['REMOTE_ADDR' => '173.245.48.10'])
        ->withHeaders(['X-Forwarded-For' => '203.0.113.77'])
        ->post($url, []);

    // Assert
    $response
        ->assertRedirect($url)
        ->assertSessionHasErrors(['first_name', 'last_name', 'email', 'password']);

    assertDatabaseCount('users', 0);
});

test('configured trusted proxy ranges have valid addresses and CIDR prefixes', function (): void {
    // Arrange
    $proxies = config()->array('trustedproxy.proxies');

    // Act
    $invalid = array_filter($proxies, function (mixed $range): bool {
        if (! is_string($range)) {
            return true;
        }

        $parts = explode('/', $range, 2);
        $address = $parts[0];
        $prefix = $parts[1] ?? null;

        return filter_var($address, FILTER_VALIDATE_IP) === false
            || ($prefix !== null && ! ctype_digit($prefix))
            || ! IpUtils::checkIp($address, $range);
    });

    // Assert
    expect($proxies)->not->toBeEmpty()
        ->and($invalid)->toBeEmpty();
});
