<?php

declare(strict_types=1);

use App\Exceptions\BaseBusinessException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Facades\Lang;

/**
 * Exposes the protected context builder the concrete exceptions share.
 */
function modelContextOf(Model $model): string
{
    $probe = new class extends BaseBusinessException
    {
        public static function context(Model $model): string
        {
            return self::formatModelContext($model);
        }
    };

    return $probe::context($model);
}

/**
 * The type word each morph alias is shown with.
 *
 * @return array<string, string>
 */
function modelTypeWords(): array
{
    return [
        'wrestler' => 'Wrestler',
        'manager' => 'Manager',
        'match' => 'Match',
        'title' => 'Title',
        'tag_team' => 'Tag team',
        'referee' => 'Referee',
        'stable' => 'Stable',
        'event' => 'Event',
        'venue' => 'Venue',
        'user' => 'User',
    ];
}

test('a model context names the kind of record in words from the translations', function (string $alias, string $type): void {
    // Arrange
    $model = new (Relation::getMorphedModel($alias));
    $model->forceFill(['name' => 'Harbor Hawk']);

    // Act
    $context = modelContextOf($model);

    // Assert
    expect($context)->toBe("{$type} 'Harbor Hawk'");
})->with(fn (): array => collect(modelTypeWords())->map(fn (string $type, string $alias): array => [$alias, $type])->all());

test('every model in the morph map has a translated type word', function (): void {
    // Arrange
    $aliases = array_keys(Relation::morphMap());
    $untranslated = array_filter($aliases, fn (string $alias): bool => ! Lang::has("core.model_types.{$alias}"));

    // Assert
    expect($aliases)->toEqualCanonicalizing(array_keys(modelTypeWords()))
        ->and($untranslated)->toBeEmpty();
});

test('a model context names a person by full name when the model has no name', function (): void {
    // Arrange
    $model = new class extends Model
    {
        public function getMorphClass(): string
        {
            return 'referee';
        }
    };
    $model->forceFill(['full_name' => 'Earl Hebner']);

    // Act
    $context = modelContextOf($model);

    // Assert
    expect($context)->toBe("Referee 'Earl Hebner'");
});

test('a model context falls back to the record key when the model has no name', function (): void {
    // Arrange
    $model = new class extends Model
    {
        public function getMorphClass(): string
        {
            return 'referee';
        }
    };
    $model->forceFill(['id' => 5]);

    // Act
    $context = modelContextOf($model);

    // Assert
    expect($context)->toBe("Referee '5'");
});
