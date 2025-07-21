<?php

declare(strict_types=1);

use App\Models\Users\User;

/**
 * Integration tests for HasNameSearch builder concern using real database queries.
 * 
 * @group builders
 * @group integration
 * @group search
 */
describe('HasNameSearch Integration Tests', function () {
    
    beforeEach(function () {
        // Create test users with various name patterns
        $this->users = [
            User::factory()->create([
                'first_name' => 'John',
                'last_name' => 'Smith',
                'email' => 'john.smith@example.com',
            ]),
            User::factory()->create([
                'first_name' => 'Jane',
                'last_name' => 'Doe', 
                'email' => 'jane.doe@example.com',
            ]),
            User::factory()->create([
                'first_name' => 'Bob',
                'last_name' => 'Johnson',
                'email' => 'bob.johnson@example.com',
            ]),
            User::factory()->create([
                'first_name' => 'Johnny',
                'last_name' => 'Cash',
                'email' => 'johnny.cash@example.com',
            ]),
            User::factory()->create([
                'first_name' => 'Michael',
                'last_name' => 'Kennedy',
                'email' => 'michael.kennedy@example.com',
            ]),
        ];
    });

    describe('whereNameMatches method', function () {
        it('finds exact first name matches', function () {
            $results = User::query()->whereNameMatches('John')->get();
            
            expect($results)->toHaveCount(1)
                ->and($results->first()->first_name)->toBe('John')
                ->and($results->first()->last_name)->toBe('Smith');
        });
        
        it('finds exact last name matches', function () {
            $results = User::query()->whereNameMatches('Doe')->get();
            
            expect($results)->toHaveCount(1)
                ->and($results->first()->first_name)->toBe('Jane')
                ->and($results->first()->last_name)->toBe('Doe');
        });
        
        it('finds first name with space prefix matches', function () {
            // Create a user with a space in the name for this specific test
            $testUser = User::factory()->create([
                'first_name' => 'Mary Jane',
                'last_name' => 'Watson',
                'email' => 'mary.jane.watson@example.com',
            ]);
            
            $results = User::query()->whereNameMatches('Mary Jane')->get();
            
            expect($results)->toHaveCount(1)
                ->and($results->first()->first_name)->toBe('Mary Jane')
                ->and($results->first()->last_name)->toBe('Watson');
        });
        
        it('does NOT match substrings that are not word boundaries', function () {
            // Searching for "John" should NOT match "Johnny" or "Johnson"  
            $results = User::query()->whereNameMatches('John')->get();
            
            expect($results)->toHaveCount(1)
                ->and($results->first()->first_name)->toBe('John')
                ->and($results->first()->last_name)->toBe('Smith');
                
            // Verify Johnny and Johnson are NOT included
            $names = $results->pluck('first_name', 'last_name')->toArray();
            expect($names)->not->toContain('Johnny')
                ->and($names)->not->toContain('Johnson');
        });
        
        it('is case insensitive', function () {
            $results = User::query()->whereNameMatches('JOHN')->get();
            
            expect($results)->toHaveCount(1)
                ->and($results->first()->first_name)->toBe('John');
                
            $results2 = User::query()->whereNameMatches('doe')->get();
            
            expect($results2)->toHaveCount(1)
                ->and($results2->first()->last_name)->toBe('Doe');
        });
        
        it('returns empty results for non-matching terms', function () {
            $results = User::query()->whereNameMatches('Nonexistent')->get();
            
            expect($results)->toHaveCount(0);
        });
    });

    describe('whereNameContains method', function () {
        it('finds names containing the search term anywhere', function () {
            $results = User::query()->whereNameContains('ohn')->get();
            
            // Should match both "John" and "Johnny" and "Johnson"
            expect($results)->toHaveCount(3);
            
            $firstNames = $results->pluck('first_name')->toArray();
            expect($firstNames)->toContain('John')
                ->and($firstNames)->toContain('Johnny');
                
            $lastNames = $results->pluck('last_name')->toArray();
            expect($lastNames)->toContain('Johnson');
        });
        
        it('is case insensitive', function () {
            $results = User::query()->whereNameContains('OHN')->get();
            
            expect($results)->toHaveCount(3); // John, Johnny, Johnson
        });
        
        it('returns empty results for non-matching terms', function () {
            $results = User::query()->whereNameContains('xyz')->get();
            
            expect($results)->toHaveCount(0);
        });
    });

    describe('method chaining', function () {
        it('can be chained with other query methods', function () {
            // Create an additional user to test with
            User::factory()->create([
                'first_name' => 'John',
                'last_name' => 'Doe',
                'email' => 'john.doe2@example.com',
            ]);
            
            $results = User::query()
                ->whereNameMatches('John')
                ->where('last_name', 'Smith')
                ->get();
            
            expect($results)->toHaveCount(1)
                ->and($results->first()->last_name)->toBe('Smith');
        });
        
        it('works with order by clauses', function () {
            $results = User::query()
                ->whereNameContains('John')  // Should get John, Johnny, Johnson
                ->orderBy('first_name')
                ->get();
            
            expect($results)->toHaveCount(3);
            
            // Check ordering: Bob (Johnson), John, Johnny
            expect($results->get(0)->first_name)->toBe('Bob')
                ->and($results->get(1)->first_name)->toBe('John')
                ->and($results->get(2)->first_name)->toBe('Johnny');
        });
    });

    describe('edge cases', function () {
        it('handles empty search terms gracefully', function () {
            $results = User::query()->whereNameMatches('')->get();
            
            // Should return no results for empty search
            expect($results)->toHaveCount(0);
        });
        
        it('handles special characters in names', function () {
            // Create user with special characters
            $specialUser = User::factory()->create([
                'first_name' => "O'Connor",
                'last_name' => 'Smith-Jones',
                'email' => 'special@example.com',
            ]);
            
            $results = User::query()->whereNameMatches("O'Connor")->get();
            
            expect($results)->toHaveCount(1)
                ->and($results->first()->first_name)->toBe("O'Connor");
        });
        
        it('handles whitespace in search terms', function () {
            $results = User::query()->whereNameMatches(' John ')->get();
            
            // The method should handle whitespace appropriately
            expect($results)->toHaveCount(1)
                ->and($results->first()->first_name)->toBe('John');
        });
    });
});