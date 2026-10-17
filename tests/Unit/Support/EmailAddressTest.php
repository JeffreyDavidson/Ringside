<?php

declare(strict_types=1);

use App\Support\EmailAddress;

it('trims and lowercases an email address', function (string $email) {
    // Arrange
    $expected = 'new.wrestler@example.test';

    // Act
    $normalized = EmailAddress::normalize($email);

    // Assert
    expect($normalized)->toBe($expected);
})->with([
    'already normalized' => ['new.wrestler@example.test'],
    'mixed case' => ['New.Wrestler@Example.TEST'],
    'surrounding whitespace' => ["  new.wrestler@example.test\t"],
]);
