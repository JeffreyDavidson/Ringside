<?php

declare(strict_types=1);

use App\Models\Users\User;
use App\Support\ModelKey;
use Illuminate\Database\Eloquent\Model;

test('returns integer and string model keys unchanged', function (Model $model, int|string $key): void {
    // Arrange
    $model->forceFill(['id' => $key]);

    // Act
    $result = ModelKey::of($model);

    // Assert
    expect($result)->toBe($key);
})->with([
    'integer key' => fn (): array => [new User, 42],
    'string key' => fn (): array => [
        new class extends Model
        {
            protected $keyType = 'string';
        },
        '0192-abc',
    ],
]);

test('rejects a model without a persisted key', function (): void {
    // Arrange
    $model = new User;

    // Act
    $resolve = fn (): int|string => ModelKey::of($model);

    // Assert
    expect($resolve)->toThrow(LogicException::class, 'User requires a persisted integer or string key.');
});
