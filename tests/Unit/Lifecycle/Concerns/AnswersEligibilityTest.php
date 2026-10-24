<?php

declare(strict_types=1);

use App\Enums\Shared\BusinessRuleReason;
use App\Exceptions\BaseBusinessException;
use App\Lifecycle\Concerns\AnswersEligibility;

final class AnsweredRefusal extends BaseBusinessException
{
    public static function refused(): static
    {
        return self::forReason(BusinessRuleReason::General, 'refused');
    }
}

final class AnsweredSubRefusal extends BaseBusinessException
{
    public static function refused(): static
    {
        return self::forReason(BusinessRuleReason::General, 'refused');
    }
}

final class SpecificRefusal extends BaseBusinessException
{
    public static function refused(): static
    {
        return self::forReason(BusinessRuleReason::General, 'refused');
    }
}

/**
 * Stand-in guard that exposes the trait's protected method.
 */
final class EligibilityAnswerer
{
    use AnswersEligibility;

    /**
     * @param  class-string<BaseBusinessException>  $refusal
     */
    public function answer(Closure $ensure, string $refusal = BaseBusinessException::class): bool
    {
        return $this->passes($ensure, $refusal);
    }
}

test('answers true when the guard does not throw', function () {
    // Arrange
    $answerer = new EligibilityAnswerer;

    // Act
    $answer = $answerer->answer(function (): void {});

    // Assert
    expect($answer)->toBeTrue();
});

test('answers false when the guard throws the named refusal', function () {
    // Arrange
    $answerer = new EligibilityAnswerer;

    // Act
    $answer = $answerer->answer(
        fn () => throw AnsweredRefusal::refused(),
        AnsweredRefusal::class,
    );

    // Assert
    expect($answer)->toBeFalse();
});

test('answers false for a subclass of the named base refusal', function () {
    // Arrange
    $answerer = new EligibilityAnswerer;

    // Act
    $answer = $answerer->answer(
        fn () => throw AnsweredSubRefusal::refused(),
        BaseBusinessException::class,
    );

    // Assert
    expect($answer)->toBeFalse();
});

test('lets a different business exception propagate', function () {
    // Arrange
    $answerer = new EligibilityAnswerer;

    // Act
    $answer = fn () => $answerer->answer(
        fn () => throw SpecificRefusal::refused(),
        AnsweredRefusal::class,
    );

    // Assert
    expect($answer)->toThrow(SpecificRefusal::class, 'refused');
});

test('lets any other exception propagate', function () {
    // Arrange
    $answerer = new EligibilityAnswerer;

    // Act
    $answer = fn () => $answerer->answer(
        fn () => throw new LogicException('broken'),
        AnsweredRefusal::class,
    );

    // Assert
    expect($answer)->toThrow(LogicException::class, 'broken');
});
