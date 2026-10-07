<?php

declare(strict_types=1);

use App\Enums\Promotions\MembershipRole;
use App\Enums\Roster\RosterLifecycleAction;
use App\Enums\Stables\StableLifecycleAction;
use App\Enums\Titles\TitleLifecycleTransition;

/**
 * Abilities that MembershipRole grants to managers and owners.
 *
 * @return list<string>
 */
function membershipContentAbilities(): array
{
    $abilities = new ReflectionClassConstant(MembershipRole::class, 'CONTENT_ABILITIES')->getValue();

    return array_values(array_filter((array) $abilities, is_string(...)));
}

/**
 * Public ability methods declared by the application policies.
 *
 * @return list<string>
 */
function policyAbilities(): array
{
    $abilities = [];

    foreach (glob(app_path('Policies/*Policy.php')) ?: [] as $file) {
        $class = 'App\\Policies\\'.basename($file, '.php');

        if (! class_exists($class)) {
            continue;
        }

        foreach (new ReflectionClass($class)->getMethods(ReflectionMethod::IS_PUBLIC) as $method) {
            if ($method->getDeclaringClass()->getName() === $class && ! $method->isConstructor()) {
                $abilities[] = $method->getName();
            }
        }
    }

    return array_values(array_unique($abilities));
}

test('every content ability granted by a membership role has a policy method', function () {
    $granted = membershipContentAbilities();
    $declared = policyAbilities();

    $withoutPolicy = array_values(array_diff($granted, $declared));

    expect($withoutPolicy)->toBeEmpty();
});

/**
 * Ability names resolved by the lifecycle enums, keyed by enum.
 *
 * @return array<string, list<string>>
 */
function lifecycleEnumAbilities(): array
{
    return [
        RosterLifecycleAction::class => array_map(fn (RosterLifecycleAction $case): string => $case->ability(), RosterLifecycleAction::cases()),
        StableLifecycleAction::class => array_map(fn (StableLifecycleAction $case): string => $case->ability(), StableLifecycleAction::cases()),
        TitleLifecycleTransition::class => array_map(fn (TitleLifecycleTransition $case): string => $case->ability(), TitleLifecycleTransition::cases()),
    ];
}

test('every lifecycle ability resolved by an enum is granted to membership roles', function (string $enum) {
    $abilities = lifecycleEnumAbilities()[$enum];
    $granted = membershipContentAbilities();

    $ungranted = array_values(array_diff($abilities, $granted));

    expect($ungranted)->toBeEmpty();
})->with([
    RosterLifecycleAction::class,
    StableLifecycleAction::class,
    TitleLifecycleTransition::class,
]);

test('every lifecycle ability resolved by an enum has a policy method', function (string $enum) {
    $abilities = lifecycleEnumAbilities()[$enum];
    $declared = policyAbilities();

    $withoutPolicy = array_values(array_diff($abilities, $declared));

    expect($withoutPolicy)->toBeEmpty();
})->with([
    RosterLifecycleAction::class,
    StableLifecycleAction::class,
    TitleLifecycleTransition::class,
]);
