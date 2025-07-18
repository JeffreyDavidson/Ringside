<?php

declare(strict_types=1);

use App\Enums\Shared\EmploymentStatus;
use App\Exceptions\Status\CannotBeRetiredException;
use App\Models\Validation\Strategies\IndividualRetirementValidation;
use App\Models\Wrestlers\Wrestler;
use Illuminate\Database\Eloquent\Model;

/**
 * Integration tests for IndividualRetirementValidation strategy.
 *
 * Tests retirement validation rules with real database models and relationships.
 * Verifies that the strategy correctly identifies when individual entities can/cannot retire.
 *
 * @see IndividualRetirementValidation
 */
describe('IndividualRetirementValidation', function () {
    beforeEach(function () {
        $this->strategy = new IndividualRetirementValidation();
    });

    test('validates retirement rules correctly', function ($factoryState, $shouldPass) {
        $wrestler = Wrestler::factory()->{$factoryState}()->create();
        
        if ($shouldPass) {
            expect(fn() => $this->strategy->validate($wrestler))->not()->toThrow();
            expectValidEntityState($wrestler);
        } else {
            expect(fn() => $this->strategy->validate($wrestler))
                ->toThrow(CannotBeRetiredException::class);
        }
    })->with([
        // Can retire: employed entities in various states
        ['employed', true],        // Changed from 'bookable' to 'employed' 
        ['suspended', true], 
        ['injured', true],
        ['released', true],
        
        // Cannot retire: invalid employment states
        ['unemployed', false],
        ['withFutureEmployment', false],
        ['retired', false],
    ]);

    describe('edge cases', function () {
        test('handles entities without required methods', function ($missingMethod) {
            $mockEntity = new class($missingMethod) extends Model {
                public function __construct(private string $skip = '') {}
                
                public function hasStatus(EmploymentStatus $status): bool {
                    return $this->skip === 'hasStatus' ? null : false;
                }
                
                public function isRetired(): bool {
                    return $this->skip === 'isRetired' ? null : false;  
                }
                
                public function hasFutureEmployment(): bool {
                    return $this->skip === 'hasFutureEmployment' ? null : false;
                }
                
                public function __call($method, $args) {
                    if ($method === $this->skip) {
                        throw new BadMethodCallException("Method {$method} does not exist");
                    }
                    return parent::__call($method, $args);
                }
            };

            // Should handle missing methods gracefully
            expect(fn() => $this->strategy->validate($mockEntity))->not()->toThrow();
        })->with([
            'hasFutureEmployment',
            'isRetired', 
            'hasStatus',
        ]);
    });
});
