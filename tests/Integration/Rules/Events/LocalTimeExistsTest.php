<?php

declare(strict_types=1);

use App\Models\Promotions\Promotion;
use App\Rules\Events\LocalTimeExists;

describe('LocalTimeExists Validation Rule', function () {
    test('it rejects a local time the promotion time zone skips when clocks move forward', function () {
        // Arrange
        $promotion = Promotion::factory()->make(['timezone' => 'America/New_York']);
        $messages = [];

        // Act
        new LocalTimeExists($promotion)->validate('date', '2026-03-08T02:30', validationFailureCallback(function (string $message) use (&$messages): void {
            $messages[] = $message;
        }));

        // Assert
        expect($messages)->toBe([__('events.date_does_not_exist', ['timezone' => 'America/New_York'])]);
    });

    test('it accepts :dataset', function (mixed $value) {
        // Arrange
        $promotion = Promotion::factory()->make(['timezone' => 'America/New_York']);
        $failed = false;

        // Act
        new LocalTimeExists($promotion)->validate('date', $value, validationFailureCallback(function () use (&$failed): void {
            $failed = true;
        }));

        // Assert
        expect($failed)->toBeFalse();
    })->with([
        'a local time that exists' => ['2026-03-08T03:30'],
        'a value that is not a string (left to the date rule)' => [null],
        'a string that is not a date (left to the date rule)' => ['not a date'],
    ]);
});
