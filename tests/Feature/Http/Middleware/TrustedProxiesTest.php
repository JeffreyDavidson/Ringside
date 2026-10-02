<?php

declare(strict_types=1);

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Testing\TestResponse;
use Symfony\Component\HttpFoundation\Response;

use function Pest\Laravel\withServerVariables;

beforeEach(function (): void {
    Route::get('/trusted-proxy-probe', fn (Request $request): array => [
        'ip' => $request->ip(),
        'secure' => $request->isSecure(),
        'host' => $request->getHost(),
    ]);
});

/**
 * @param  array<string, string>  $headers
 * @return TestResponse<Response>
 */
function probeFrom(string $peer, array $headers = []): TestResponse
{
    return withServerVariables(['REMOTE_ADDR' => $peer])
        ->withHeaders($headers)
        ->getJson('/trusted-proxy-probe');
}

test('the real client address is used when the request comes through Cloudflare', function (string $cloudflarePeer): void {
    // Arrange
    $headers = ['X-Forwarded-For' => '203.0.113.9'];

    // Act
    $response = probeFrom($cloudflarePeer, $headers);

    // Assert
    $response->assertJsonPath('ip', '203.0.113.9');
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
    $response->assertJsonPath('ip', '203.0.113.9');
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
        ->assertJsonPath('ip', '198.51.100.7')
        ->assertJsonPath('secure', false);
});

test('the forwarded protocol is trusted from Cloudflare', function (): void {
    // Arrange
    $headers = ['X-Forwarded-Proto' => 'https'];

    // Act
    $response = probeFrom('173.245.48.10', $headers);

    // Assert
    $response->assertJsonPath('secure', true);
});

test('a forwarded host header is never trusted, even from Cloudflare', function (): void {
    // Arrange
    $headers = ['X-Forwarded-Host' => 'evil.example'];

    // Act
    $response = probeFrom('173.245.48.10', $headers);

    // Assert
    $response->assertJsonPath('host', fn (string $host): bool => $host !== 'evil.example');
});

test('visitors behind the same Cloudflare address are throttled separately', function (): void {
    // Arrange
    $url = route('register');
    foreach (range(1, 6) as $attempt) {
        withServerVariables(['REMOTE_ADDR' => '173.245.48.10'])
            ->withHeaders(['X-Forwarded-For' => '203.0.113.9'])
            ->post($url, []);
    }

    // Act
    $sameVisitor = withServerVariables(['REMOTE_ADDR' => '173.245.48.10'])
        ->withHeaders(['X-Forwarded-For' => '203.0.113.9'])
        ->post($url, []);
    $otherVisitor = withServerVariables(['REMOTE_ADDR' => '173.245.48.10'])
        ->withHeaders(['X-Forwarded-For' => '203.0.113.77'])
        ->post($url, []);

    // Assert
    $sameVisitor->assertTooManyRequests();
    expect($otherVisitor->getStatusCode())->not->toBe(429);
});

test('the default trusted proxies are valid address ranges', function (): void {
    // Arrange
    $proxies = config()->array('trustedproxy.proxies');

    // Act
    $invalid = array_filter($proxies, fn (mixed $range): bool => ! is_string($range)
        || filter_var(explode('/', $range)[0], FILTER_VALIDATE_IP) === false);

    // Assert
    expect($proxies)->not->toBeEmpty()
        ->and($invalid)->toBeEmpty();
});
