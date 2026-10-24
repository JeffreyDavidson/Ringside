<?php

declare(strict_types=1);

use App\Enums\Shared\BusinessRuleReason;
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

test('a model context leaves the name empty for a record that has neither a name nor a key', function (): void {
    // Arrange
    $model = new class extends Model
    {
        public function getMorphClass(): string
        {
            return 'referee';
        }
    };

    // Act
    $context = modelContextOf($model);

    // Assert
    expect($context)->toBe("Referee ''");
});

/**
 * Exposes the protected model-context factory the concrete exceptions share.
 *
 * @param  array<string, string>  $replacements
 */
function exceptionForModel(string $key, Model $model, array $replacements = [], BusinessRuleReason $reason = BusinessRuleReason::General, ?int $count = null): BaseBusinessException
{
    $probe = new class extends BaseBusinessException
    {
        /** @param array<string, string> $replacements */
        public static function build(string $key, Model $model, array $replacements, BusinessRuleReason $reason, ?int $count): static
        {
            return self::forModel($key, $model, $replacements, $reason, $count);
        }
    };

    return $probe::build($key, $model, $replacements, $reason, $count);
}

test('a model exception message puts the model context into the translated message', function (): void {
    // Arrange
    $stable = (new (Relation::getMorphedModel('stable')))->forceFill(['name' => 'The Harbor Crew']);

    // Act
    $exception = exceptionForModel('stables.errors.deleted.already_deleted', $stable);

    // Assert
    expect($exception->getMessage())->toBe(__('stables.errors.deleted.already_deleted', ['context' => "Stable 'The Harbor Crew'"]))
        ->and($exception->getMessage())->toContain("Stable 'The Harbor Crew'")
        ->and($exception->reason())->toBe(BusinessRuleReason::General)
        ->and($exception->getCode())->toBe(0);
});

test('a model exception message adds the extra replacements and the reason', function (): void {
    // Arrange
    $referee = new class extends Model
    {
        public function getMorphClass(): string
        {
            return 'referee';
        }
    };
    $referee->forceFill(['full_name' => 'Earl Hebner']);

    // Act
    $exception = exceptionForModel('individuals.errors.suspend.already_suspended', $referee, ['details' => ' since Monday'], BusinessRuleReason::AlreadySuspended);

    // Assert
    expect($exception->getMessage())->toBe("Referee 'Earl Hebner' is already suspended since Monday.")
        ->and($exception->reason())->toBe(BusinessRuleReason::AlreadySuspended);
});

test('a model exception message picks the plural form from the count', function (int $count): void {
    // Arrange
    $stable = (new (Relation::getMorphedModel('stable')))->forceFill(['name' => 'The Harbor Crew']);

    // Act
    $exception = exceptionForModel('stables.errors.deleted.has_current_members', $stable, count: $count);

    // Assert
    expect($exception->getMessage())->toBe(trans_choice('stables.errors.deleted.has_current_members', $count, ['context' => "Stable 'The Harbor Crew'"]));
})->with([1, 3]);
