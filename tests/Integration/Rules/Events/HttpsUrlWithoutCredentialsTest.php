<?php

declare(strict_types=1);

use App\Rules\Events\HttpsUrlWithoutCredentials;
use Illuminate\Support\Facades\Validator;

describe('HttpsUrlWithoutCredentials Validation Rule', function () {
    test('it rejects :dataset', function (string $value) {
        // Arrange
        $validator = Validator::make(['url' => $value], ['url' => [new HttpsUrlWithoutCredentials]]);

        // Act
        $messages = $validator->errors()->get('url');

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
        $validator = Validator::make(['url' => $value], ['url' => [new HttpsUrlWithoutCredentials]]);

        // Act
        $passes = $validator->passes();

        // Assert
        expect($passes)->toBeTrue();
    })->with([
        'a plain link' => ['https://example.com/track?id=1'],
        'a link with a port' => ['https://example.com:8443/track'],
        'a value that is not a string (left to the url rule)' => [12],
    ]);
});
