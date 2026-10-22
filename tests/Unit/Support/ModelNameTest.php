<?php

declare(strict_types=1);

use App\Support\ModelName;
use Illuminate\Database\Eloquent\Model;

test('names a record by its name, then its full name, then its key', function (array $attributes, string $name): void {
    // Arrange
    $model = new class extends Model {};
    $model->forceFill($attributes);

    // Act
    $result = ModelName::of($model);

    // Assert
    expect($result)->toBe($name);
})->with([
    'name' => [['id' => 5, 'name' => 'Harbor Hawk', 'full_name' => 'Ignored Person'], 'Harbor Hawk'],
    'full name' => [['id' => 5, 'full_name' => 'Earl Hebner'], 'Earl Hebner'],
    'name that is not text' => [['id' => 5, 'name' => 12, 'full_name' => 'Earl Hebner'], 'Earl Hebner'],
    'key' => [['id' => 5], '5'],
    'record not saved yet' => [[], ''],
]);
