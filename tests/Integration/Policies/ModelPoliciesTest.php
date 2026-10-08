<?php

declare(strict_types=1);

use App\Models\Users\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Gate;

/**
 * The model policies deny every ability on their own; the global Gate::before hook (PromotionGate) allows
 * administrators. The ability lists and the cases come from the 'model policies' and 'policy abilities' datasets
 * in tests/Datasets/Policies.php.
 *
 * PromotionPolicy keeps its own test because PromotionGate decides promotion abilities from memberships.
 */
function policyTestUser(string $actor): User
{
    return $actor === 'administrator' ? administrator() : basicUser();
}

function policyTestSubject(string $policy, bool $forInstance): Model|string
{
    $catalog = policyAbilityCatalog()[$policy];

    return $forInstance ? $catalog['create']() : $catalog['model'];
}

test('a policy denies the ability to basic users when it is called directly', function (string $policy, string $model, string $ability, bool $forInstance) {
    // Arrange
    $basicUser = basicUser();
    $subject = policyTestSubject($policy, $forInstance);
    $arguments = $forInstance ? [$subject] : [];

    // Act
    $decision = (new $policy)->{$ability}($basicUser, ...$arguments);

    // Assert
    expect($decision)->toBeFalse();
})->with('policy abilities');

test('the Gate hook decides the ability for administrators only when no subject is given', function (string $policy, string $model, string $ability, bool $forInstance, string $actor, ?bool $expected) {
    // Arrange
    $user = policyTestUser($actor);

    // Act
    $decision = Gate::forUser($user)->raw($ability);

    // Assert
    expect($decision)->toBe($expected);
})->with('policy abilities')->with('gate hook decisions');

test('the Gate allows the ability for administrators and denies it for basic users', function (string $policy, string $model, string $ability, bool $forInstance, string $actor, bool $allowed) {
    // Arrange
    $user = policyTestUser($actor);
    $subject = policyTestSubject($policy, $forInstance);

    // Act
    $decision = Gate::forUser($user)->allows($ability, $subject);

    // Assert
    expect($decision)->toBe($allowed);
})->with('policy abilities')->with('gate subject decisions');

test('the Gate hook decides abilities no policy declares for administrators only', function (string $ability, string $actor, ?bool $expected) {
    // Arrange
    $user = policyTestUser($actor);

    // Act
    $decision = Gate::forUser($user)->raw($ability);

    // Assert
    expect($decision)->toBe($expected);
})->with('abilities without a policy method')->with('gate hook decisions');

test('a policy treats a model in any state like any other', function (string $policy, Closure $createSubject) {
    // Arrange
    $subject = $createSubject();
    $basicUser = basicUser();
    $administrator = administrator();
    $abilities = collect(policyAbilityCatalog()[$policy]['instance']);

    // Act
    $directDecisions = $abilities->mapWithKeys(fn (string $ability): array => [$ability => (new $policy)->{$ability}($basicUser, $subject)]);
    $administratorDecisions = $abilities->mapWithKeys(fn (string $ability): array => [$ability => Gate::forUser($administrator)->allows($ability, $subject)]);
    $basicUserDecisions = $abilities->mapWithKeys(fn (string $ability): array => [$ability => Gate::forUser($basicUser)->allows($ability, $subject)]);

    // Assert
    expect($directDecisions->all())->toBe($abilities->mapWithKeys(fn (string $ability): array => [$ability => false])->all())
        ->and($administratorDecisions->all())->toBe($abilities->mapWithKeys(fn (string $ability): array => [$ability => true])->all())
        ->and($basicUserDecisions->all())->toBe($abilities->mapWithKeys(fn (string $ability): array => [$ability => false])->all());
})->with('policy subject states');

describe('every model policy', function () {
    test('declares exactly the abilities in the dataset', function (string $policy, string $model, array $classAbilities, array $instanceAbilities) {
        // Arrange
        $expected = [...$classAbilities, ...$instanceAbilities];

        // Act
        $declared = collect(new ReflectionClass(new $policy)->getMethods(ReflectionMethod::IS_PUBLIC))
            ->filter(fn (ReflectionMethod $method): bool => $method->getDeclaringClass()->getName() === $policy)
            ->map(fn (ReflectionMethod $method): string => $method->getName())
            ->all();

        // Assert
        expect($declared)->toEqualCanonicalizing($expected);
    })->with('model policies');

    test('leaves administrator bypass to the global Gate hook instead of a before method', function (string $policy) {
        // Act
        $methods = get_class_methods($policy);

        // Assert
        expect($methods)->not->toContain('before');
    })->with('model policies');

    test('takes the user, then the model for instance abilities, and returns a boolean', function (string $policy, string $model, array $classAbilities, array $instanceAbilities) {
        // Act
        $signatures = collect([...$classAbilities, ...$instanceAbilities])
            ->mapWithKeys(function (string $ability) use ($policy): array {
                $method = new ReflectionMethod($policy, $ability);
                $parameters = collect($method->getParameters())->map(fn (ReflectionParameter $parameter): string => reflectionTypeName($parameter));

                return [$ability => [...$parameters->all(), reflectionReturnTypeName($method)]];
            })
            ->all();

        // Assert
        expect($signatures)->toBe([
            ...array_fill_keys($classAbilities, [User::class, 'bool']),
            ...array_fill_keys($instanceAbilities, [User::class, $model, 'bool']),
        ]);
    })->with('model policies');

    test('is resolved by the Gate for its model class and its instances', function (string $policy, string $model) {
        // Arrange
        $instance = policyTestSubject($policy, true);

        // Act
        $forClass = Gate::getPolicyFor($model);
        $forInstance = Gate::getPolicyFor($instance);

        // Assert
        expect(get_debug_type($forClass))->toBe($policy)
            ->and(get_debug_type($forInstance))->toBe($policy);
    })->with('model policies');

    test('gives the same decisions across instances and repeated calls', function (string $policy, string $model, array $classAbilities) {
        // Arrange
        $basicUser = basicUser();
        $administrator = administrator();
        $ability = $classAbilities[0];
        $first = new $policy;
        $second = new $policy;

        // Act
        $firstDecision = $first->{$ability}($basicUser);
        $secondDecision = $second->{$ability}($basicUser);
        $repeatedDecision = $first->{$ability}($basicUser);
        $administratorDecisions = [
            Gate::forUser($administrator)->raw('create'),
            Gate::forUser($administrator)->raw('create'),
        ];

        // Assert
        expect($firstDecision)->toBeFalse()
            ->and($secondDecision)->toBe($firstDecision)
            ->and($repeatedDecision)->toBe($firstDecision)
            ->and($administratorDecisions)->toBe([true, true]);
    })->with('model policies');
});
