<?php

declare(strict_types=1);

test('deprecations are discarded when the environment names the null channel', function () {
    // Act
    $config = configFileWith('logging', [
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
    $config = configFileWith('logging', [
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
    $config = configFileWith('logging', [
        'LOG_DEPRECATIONS_CHANNEL' => null,
        'LOG_DEPRECATIONS_TRACE' => null,
    ]);

    // Assert
    expect($config['deprecations'])->toBe([
        'channel' => 'null',
        'trace' => false,
    ]);
});
