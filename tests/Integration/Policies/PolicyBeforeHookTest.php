<?php

declare(strict_types=1);

use App\Policies\EventPolicy;
use App\Policies\ManagerPolicy;
use App\Policies\MatchPolicy;
use App\Policies\RefereePolicy;
use App\Policies\StablePolicy;
use App\Policies\TagTeamPolicy;
use App\Policies\TitlePolicy;
use App\Policies\UserPolicy;
use App\Policies\VenuePolicy;
use App\Policies\WrestlerPolicy;
use Illuminate\Support\Facades\Gate;

/**
 * Shared tests for global Gate hook.
 *
 * This test ensures all policies follow the consistent global Gate hook
 * where administrators bypass all authorization checks and basic users
 * continue to individual method checks.
 */
describe('Global Gate Hook Pattern', function () {

    beforeEach(function () {
        $this->policies = [
            new MatchPolicy(),
            new EventPolicy(),
            new ManagerPolicy(),
            new RefereePolicy(),
            new StablePolicy(),
            new TagTeamPolicy(),
            new TitlePolicy(),
            new UserPolicy(),
            new VenuePolicy(),
            new WrestlerPolicy(),
        ];

        $this->admin = administrator();
        $this->basicUser = basicUser();
    });

    test('administrators bypass all authorization methods', function () {
        foreach ($this->policies as $policy) {
            // Test arbitrary ability (proves global Gate hook works for non-method abilities)
            expect(Gate::forUser($this->admin)->raw('any-ability'))->toBeTrue();

            // Test all actual policy methods
            $methods = getPublicPolicyMethods($policy);
            foreach ($methods as $methodName) {
                expect(Gate::forUser($this->admin)->raw($methodName))
                    ->toBeTrue("Admin should bypass {$methodName} in ".get_class($policy));
            }
        }
    });

    test('basic users continue to individual method checks', function () {
        foreach ($this->policies as $policy) {
            // Test arbitrary ability (proves global Gate hook works for non-method abilities)
            expect(Gate::forUser($this->basicUser)->raw('any-ability'))->toBeNull();

            // Test all actual policy methods
            $methods = getPublicPolicyMethods($policy);
            foreach ($methods as $methodName) {
                expect(Gate::forUser($this->basicUser)->raw($methodName))
                    ->toBeNull("Basic user should continue to {$methodName} check in ".get_class($policy));
            }
        }
    });

});

/**
 * Helper function to get public policy methods excluding 'before' and magic methods.
 *
 * @return array<int, string>
 */
function getPublicPolicyMethods(object $policy): array
{
    $reflection = new ReflectionClass($policy);
    $methods = $reflection->getMethods(ReflectionMethod::IS_PUBLIC);

    return array_map(
        fn (ReflectionMethod $method) => $method->getName(),
        array_filter(
            $methods,
            fn (ReflectionMethod $method) => $method->getName() !== 'before' &&
                ! str_starts_with($method->getName(), '__')
        )
    );
}
