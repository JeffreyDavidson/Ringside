<?php

declare(strict_types=1);

/**
 * Load config/logging.php with the given environment variables set, or unset when null.
 *
 * @param  array<string, ?string>  $variables
 * @return array<string, mixed>
 */
function loggingConfigWith(array $variables): array
{
    $previous = [];

    foreach ($variables as $name => $value) {
        $previous[$name] = [$_SERVER[$name] ?? null, $_ENV[$name] ?? null, getenv($name)];
        unset($_SERVER[$name], $_ENV[$name]);
        putenv($name);

        if ($value !== null) {
            $_SERVER[$name] = $value;
            $_ENV[$name] = $value;
            putenv("{$name}={$value}");
        }
    }

    try {
        return require config_path('logging.php');
    } finally {
        foreach ($previous as $name => [$server, $env, $process]) {
            unset($_SERVER[$name], $_ENV[$name]);
            putenv($name);

            if ($server !== null) {
                $_SERVER[$name] = $server;
            }

            if ($env !== null) {
                $_ENV[$name] = $env;
            }

            if ($process !== false) {
                putenv("{$name}={$process}");
            }
        }
    }
}

test('deprecations are discarded when the environment names the null channel', function () {
    // Act
    $config = loggingConfigWith([
        'LOG_DEPRECATIONS_CHANNEL' => 'null',
        'LOG_DEPRECATIONS_TRACE' => null,
    ]);

    // Assert
    expect($config['deprecations'])->toBe([
        'channel' => null,
        'trace' => false,
    ]);
});

test('deprecations are routed by the environment', function () {
    // Act
    $config = loggingConfigWith([
        'LOG_DEPRECATIONS_CHANNEL' => 'daily',
        'LOG_DEPRECATIONS_TRACE' => 'true',
    ]);

    // Assert
    expect($config['deprecations'])->toBe([
        'channel' => 'daily',
        'trace' => true,
    ]);
});

test('deprecations are discarded without a trace when the environment does not set them', function () {
    // Act
    $config = loggingConfigWith([
        'LOG_DEPRECATIONS_CHANNEL' => null,
        'LOG_DEPRECATIONS_TRACE' => null,
    ]);

    // Assert
    expect($config['deprecations'])->toBe([
        'channel' => 'null',
        'trace' => false,
    ]);
});
