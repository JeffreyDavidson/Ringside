<?php

declare(strict_types=1);

use App\Models\Managers\Manager;
use App\Models\Referees\Referee;
use App\Models\Users\User;
use App\Models\Wrestlers\Wrestler;

describe('name search', function () {
    it('searches users by a case-insensitive first or last name match', function () {
        $johnSmith = User::factory()->create([
            'first_name' => 'John',
            'last_name' => 'Smith',
        ]);
        $janeDoe = User::factory()->create([
            'first_name' => 'Jane',
            'last_name' => 'Doe',
        ]);
        User::factory()->create([
            'first_name' => 'Johnny',
            'last_name' => 'Johnson',
        ]);

        $firstNameMatches = User::query()->whereNameMatches(' JOHN ')->get();
        $lastNameMatches = User::query()->whereNameMatches('doe')->get();

        expect($firstNameMatches)->toHaveCount(1)
            ->and($firstNameMatches->firstOrFail()->is($johnSmith))->toBeTrue()
            ->and($lastNameMatches)->toHaveCount(1)
            ->and($lastNameMatches->firstOrFail()->is($janeDoe))->toBeTrue();
    });

    it('searches managers by name', function () {
        $manager = Manager::factory()->create([
            'first_name' => 'Bobby',
            'last_name' => 'Heenan',
        ]);
        Manager::factory()->create([
            'first_name' => 'Jimmy',
            'last_name' => 'Hart',
        ]);

        $matches = Manager::query()->whereNameMatches('Heenan')->get();

        expect($matches)->toHaveCount(1)
            ->and($matches->firstOrFail()->is($manager))->toBeTrue();
    });

    it('searches referees by name', function () {
        $referee = Referee::factory()->create([
            'first_name' => 'Earl',
            'last_name' => 'Hebner',
        ]);
        Referee::factory()->create([
            'first_name' => 'Nick',
            'last_name' => 'Patrick',
        ]);

        $matches = Referee::query()->whereNameMatches('Earl')->get();

        expect($matches)->toHaveCount(1)
            ->and($matches->firstOrFail()->is($referee))->toBeTrue();
    });

    it('does not expose first and last name search on wrestler builders', function () {
        expect(method_exists(Wrestler::query(), 'whereNameMatches'))->toBeFalse();
    });
});
