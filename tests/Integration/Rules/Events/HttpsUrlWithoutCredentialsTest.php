<?php

declare(strict_types=1);

use App\Rules\Events\HttpsUrlWithoutCredentials;

describe('HttpsUrlWithoutCredentials Validation Rule', function () {
    test('it rejects :dataset', function (mixed $value) {
        // Arrange
        $messages = [];

        // Act
        new HttpsUrlWithoutCredentials()->validate('cues.0.url', $value, validationFailureCallback(function (string $message) use (&$messages): void {
            $messages[] = $message;
        }));

        // Assert
        expect($messages)->toBe([__('production.invalid_cue')]);
    })->with([
        'a user name' => ['https://secret@example.com/track'],
        'a user name and password' => ['https://secret:password@example.com/track'],
        'a password without a user name' => ['https://:password@example.com/track'],
        'a link that cannot be parsed' => ['https:///example.com'],
    ]);

    test('it accepts :dataset', function (mixed $value) {
        // Arrange
        $failed = false;

        // Act
        new HttpsUrlWithoutCredentials()->validate('cues.0.url', $value, validationFailureCallback(function () use (&$failed): void {
            $failed = true;
        }));

        // Assert
        expect($failed)->toBeFalse();
    })->with([
        'a plain link' => ['https://example.com/track?id=1'],
        'a link with a port' => ['https://example.com:8443/track'],
        'a value that is not a string (left to the url rule)' => [12],
    ]);
});
