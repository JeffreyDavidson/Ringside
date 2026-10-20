<?php

declare(strict_types=1);

use App\Enums\Events\SponsorInstructionMode;
use App\ValueObjects\PackKey;

test('it reads a stored pack key and writes it back unchanged', function (string $key, SponsorInstructionMode $kind): void {
    // Arrange
    $uuid = '0197c3a0-5b1e-7c0e-8a55-3f0f4f1d2b6a';

    // Act
    $packKey = PackKey::fromString("{$key}:{$uuid}");

    // Assert
    expect($packKey->kind)->toBe($kind)
        ->and($packKey->uuid)->toBe($uuid)
        ->and((string) $packKey)->toBe("{$key}:{$uuid}");
})->with([
    'segment' => ['segment', SponsorInstructionMode::Segment],
    'setup' => ['setup', SponsorInstructionMode::Setup],
]);

test('it rejects a key that is not a segment or setup followed by a uuid', function (string $key): void {
    // Act
    $build = fn (): PackKey => PackKey::fromString($key);

    // Assert
    expect($build)->toThrow(InvalidArgumentException::class);
})->with([
    'empty' => [''],
    'no uuid' => ['segment'],
    'remove kind' => ['remove:0197c3a0-5b1e-7c0e-8a55-3f0f4f1d2b6a'],
    'unknown kind' => ['banner:0197c3a0-5b1e-7c0e-8a55-3f0f4f1d2b6a'],
    'bad uuid' => ['segment:not-a-uuid'],
]);
