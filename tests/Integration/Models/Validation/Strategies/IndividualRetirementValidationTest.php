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
            $this->strategy->validate($wrestler);
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
        test('handles entities without hasFutureEmployment method', function () {
            $mockEntity = new class extends Model {
                public function hasStatus(EmploymentStatus $status): bool {
                    return false;
                }
                
                public function isRetired(): bool {
                    return false;  
                }
                
                // Note: hasFutureEmployment method intentionally missing
            };

            // Should handle missing hasFutureEmployment method gracefully
            $this->strategy->validate($mockEntity);
        });

        test('handles entities without isRetired method', function () {
            $mockEntity = new class extends Model {
                public function hasStatus(EmploymentStatus $status): bool {
                    return false;
                }
                
                public function hasFutureEmployment(): bool {
                    return false;
                }
                
                // Note: isRetired method intentionally missing
            };

            // Should handle missing isRetired method gracefully
            $this->strategy->validate($mockEntity);
        });

        test('handles entities without hasStatus method', function () {
            $mockEntity = new class extends Model {
                public function isRetired(): bool {
                    return false;  
                }
                
                public function hasFutureEmployment(): bool {
                    return false;
                }
                
                // Note: hasStatus method intentionally missing
            };

            // Should handle missing hasStatus method gracefully
            $this->strategy->validate($mockEntity);
        });
    });
});
