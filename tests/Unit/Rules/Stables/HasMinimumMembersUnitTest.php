<?php

declare(strict_types=1);

use App\Models\Stables\Stable;
use App\Models\TagTeams\TagTeam;
use App\Models\Wrestlers\Wrestler;
use App\Rules\Stables\HasMinimumMembers;
use Illuminate\Support\Collection;

/**
 * Unit tests for HasMinimumMembers validation rule.
 *
 * UNIT TEST SCOPE:
 * - Mathematical calculation logic (tag teams * 2 + wrestlers)
 * - Collection counting and member calculation
 * - Minimum member threshold validation against Stable constant
 * - Error message formatting with dynamic counts
 * - Edge cases (empty collections, various combinations)
 *
 * These tests verify the HasMinimumMembers rule logic independently
 * of models, database, or Laravel's validation framework.
 *
 * @see HasMinimumMembers
 */
describe('HasMinimumMembers Validation Rule Unit Tests', function () {
    describe('member calculation logic', function () {
        test('calculates correct total with wrestlers only', function () {
            // Arrange
            $wrestlers = new Collection([new Wrestler(), new Wrestler(), new Wrestler()]);
            $tagTeams = new Collection();
            $rule = new HasMinimumMembers($wrestlers, $tagTeams);

            $failCalled = false;
            $failCallback = function (string $message) use (&$failCalled) {
                $failCalled = true;
            };

            // Act
            $rule->validate('members', 'test', validationFailureCallback($failCallback));

            // Assert
            expect($failCalled)->toBeFalse(); // 3 wrestlers >= 3 minimum
        });

        test('calculates correct total with tag teams only', function () {
            // Arrange - 2 tag teams = 4 members (2 each)
            $wrestlers = new Collection();
            $tagTeams = new Collection([new TagTeam(), new TagTeam()]);
            $rule = new HasMinimumMembers($wrestlers, $tagTeams);

            $failCalled = false;
            $failCallback = function (string $message) use (&$failCalled) {
                $failCalled = true;
            };

            // Act
            $rule->validate('members', 'test', validationFailureCallback($failCallback));

            // Assert
            expect($failCalled)->toBeFalse(); // 4 members >= 3 minimum
        });

        test('calculates correct total with mixed members', function () {
            // Arrange - 1 wrestler + 1 tag team = 3 members
            $wrestlers = new Collection([new Wrestler()]);
            $tagTeams = new Collection([new TagTeam()]);
            $rule = new HasMinimumMembers($wrestlers, $tagTeams);

            $failCalled = false;
            $failCallback = function (string $message) use (&$failCalled) {
                $failCalled = true;
            };

            // Act
            $rule->validate('members', 'test', validationFailureCallback($failCallback));

            // Assert
            expect($failCalled)->toBeFalse(); // 3 members = 3 minimum
        });

        test('tag team multiplication logic is correct', function () {
            // Arrange - Test that each tag team counts as exactly 2 members
            $wrestlers = new Collection();
            $tagTeams = new Collection([new TagTeam(), new TagTeam(), new TagTeam()]); // 3 teams = 6 members
            $rule = new HasMinimumMembers($wrestlers, $tagTeams);

            $failCalled = false;
            $failCallback = function (string $message) use (&$failCalled) {
                $failCalled = true;
            };

            // Act
            $rule->validate('members', 'test', validationFailureCallback($failCallback));

            // Assert
            expect($failCalled)->toBeFalse(); // 6 members > 3 minimum
        });
    });

    describe('minimum threshold validation', function () {
        test('validation fails when total is below minimum', function () {
            // Arrange - Only 2 wrestlers, need minimum 3
            $wrestlers = new Collection([new Wrestler(), new Wrestler()]);
            $tagTeams = new Collection();
            $rule = new HasMinimumMembers($wrestlers, $tagTeams);

            $failCalled = false;
            $failMessage = '';
            $failCallback = function (string $message) use (&$failCalled, &$failMessage) {
                $failCalled = true;
                $failMessage = $message;
            };

            // Act
            $rule->validate('members', 'test', validationFailureCallback($failCallback));

            // Assert
            expect($failCalled)->toBeTrue();
            expect($failMessage)->toContain('A stable must have at least');
            expect($failMessage)->toContain('members. Currently adding 2 members.');
        });

        test('validation passes when total equals minimum', function () {
            // Arrange - Exactly 3 members
            $wrestlers = new Collection([new Wrestler(), new Wrestler(), new Wrestler()]);
            $tagTeams = new Collection();
            $rule = new HasMinimumMembers($wrestlers, $tagTeams);

            $failCalled = false;
            $failCallback = function (string $message) use (&$failCalled) {
                $failCalled = true;
            };

            // Act
            $rule->validate('members', 'test', validationFailureCallback($failCallback));

            // Assert
            expect($failCalled)->toBeFalse();
        });

        test('validation passes when total exceeds minimum', function () {
            // Arrange - 5 wrestlers > 3 minimum
            $wrestlers = Collection::times(5, fn (): Wrestler => new Wrestler());
            $tagTeams = new Collection();
            $rule = new HasMinimumMembers($wrestlers, $tagTeams);

            $failCalled = false;
            $failCallback = function (string $message) use (&$failCalled) {
                $failCalled = true;
            };

            // Act
            $rule->validate('members', 'test', validationFailureCallback($failCallback));

            // Assert
            expect($failCalled)->toBeFalse();
        });
    });

    describe('error message formatting', function () {
        test('error message includes correct minimum count', function () {
            // Arrange
            $wrestlers = new Collection([new Wrestler()]); // Only 1 member
            $tagTeams = new Collection();
            $rule = new HasMinimumMembers($wrestlers, $tagTeams);

            $failMessage = '';
            $failCallback = function (string $message) use (&$failMessage) {
                $failMessage = $message;
            };

            // Act
            $rule->validate('members', 'test', validationFailureCallback($failCallback));

            // Assert
            expect($failMessage)->toContain((string) Stable::MIN_MEMBERS_COUNT);
        });

        test('error message includes actual member count', function () {
            // Arrange - 1 wrestler + 1 tag team = 3 total, but let's make it 2
            $wrestlers = new Collection([new Wrestler()]);
            $tagTeams = new Collection(); // Only 1 member total
            $rule = new HasMinimumMembers($wrestlers, $tagTeams);

            $failMessage = '';
            $failCallback = function (string $message) use (&$failMessage) {
                $failMessage = $message;
            };

            // Act
            $rule->validate('members', 'test', validationFailureCallback($failCallback));

            // Assert
            expect($failMessage)->toContain('Currently adding 1 members.');
        });

        test('error message format is consistent', function () {
            // Arrange - Test with different counts to verify format consistency
            $wrestlers = new Collection();
            $tagTeams = new Collection(); // 0 members
            $rule = new HasMinimumMembers($wrestlers, $tagTeams);

            $failMessage = '';
            $failCallback = function (string $message) use (&$failMessage) {
                $failMessage = $message;
            };

            // Act
            $rule->validate('members', 'test', validationFailureCallback($failCallback));

            // Assert
            $expectedMessage = 'A stable must have at least '.Stable::MIN_MEMBERS_COUNT.' members. Currently adding 0 members.';
            expect($failMessage)->toBe($expectedMessage);
        });
    });

    describe('edge cases and collection handling', function () {
        test('handles empty collections', function () {
            // Arrange
            $wrestlers = new Collection();
            $tagTeams = new Collection();
            $rule = new HasMinimumMembers($wrestlers, $tagTeams);

            $failCalled = false;
            $failCallback = function (string $message) use (&$failCalled) {
                $failCalled = true;
            };

            // Act
            $rule->validate('members', 'test', validationFailureCallback($failCallback));

            // Assert
            expect($failCalled)->toBeTrue(); // 0 < 3 minimum
        });

        test('handles large collections efficiently', function () {
            // Arrange - Large collections to test performance
            $wrestlers = Collection::times(50, fn (): Wrestler => new Wrestler());
            $tagTeams = Collection::times(25, fn (): TagTeam => new TagTeam()); // 25 tag teams = 50 members
            $rule = new HasMinimumMembers($wrestlers, $tagTeams);
            // Total: 50 + 50 = 100 members

            $failCalled = false;
            $failCallback = function (string $message) use (&$failCalled) {
                $failCalled = true;
            };

            // Act
            $rule->validate('members', 'test', validationFailureCallback($failCallback));

            // Assert
            expect($failCalled)->toBeFalse(); // 100 > 3 minimum
        });

        test('attribute and value parameters do not affect calculation', function () {
            // Arrange
            $wrestlers = new Collection([new Wrestler(), new Wrestler()]);
            $tagTeams = new Collection();
            $rule = new HasMinimumMembers($wrestlers, $tagTeams);

            $failCallCount = 0;
            $failCallback = function (string $message) use (&$failCallCount) {
                $failCallCount++;
            };

            // Act - Test with different attribute names and values
            $rule->validate('members', 'test1', validationFailureCallback($failCallback));
            $rule->validate('different_field', 'test2', validationFailureCallback($failCallback));
            $rule->validate('anything', null, validationFailureCallback($failCallback));

            // Assert
            expect($failCallCount)->toBe(3); // All should fail consistently
        });
    });

    describe('constant integration', function () {
        test('uses correct minimum members constant', function () {
            // This test ensures we're using the right constant value
            expect(Stable::MIN_MEMBERS_COUNT)->toBe(3);
        });

        test('calculation logic aligns with business rules', function () {
            // Arrange - Test edge case around the minimum
            $wrestlers = new Collection([new Wrestler()]);
            $tagTeams = new Collection([new TagTeam()]); // 1 tag team = 2 members
            $rule = new HasMinimumMembers($wrestlers, $tagTeams);
            // Total: 1 + 2 = 3 members (exactly minimum)

            $failCalled = false;
            $failCallback = function (string $message) use (&$failCalled) {
                $failCalled = true;
            };

            // Act
            $rule->validate('members', 'test', validationFailureCallback($failCallback));

            // Assert
            expect($failCalled)->toBeFalse(); // Should pass with exactly 3 members
        });
    });
});
