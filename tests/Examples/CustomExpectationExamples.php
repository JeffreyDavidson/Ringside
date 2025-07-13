<?php

declare(strict_types=1);

use Ankurk91\Eloquent\Relations\BelongsToOne;
use App\Builders\Roster\WrestlerBuilder;
use App\Enums\Shared\EmploymentStatus;
use App\Models\Concerns\CanJoinStables;
use App\Models\Concerns\IsEmployable;
use App\Models\Events\Event;
use App\Models\Managers\Manager;
use App\Models\Matches\EventMatch;
use App\Models\Matches\MatchType;
use App\Models\Titles\Title;
use App\Models\Wrestlers\Wrestler;
use App\Models\Wrestlers\WrestlerEmployment;
use function Pest\Laravel\assertDatabaseCount;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Artisan;

/**
 * Examples of how custom expectations improve test readability and maintainability.
 *
 * These examples show before/after comparisons of test code using custom expectations
 * versus traditional assertions. This file is for documentation purposes only.
 */

/**
 * EMPLOYMENT STATUS TESTING
 */

// BEFORE: Using traditional assertions
test('wrestler employment status - traditional way', function () {
    $wrestler = Wrestler::factory()->employed()->create();

    expect($wrestler->status)->toBe(EmploymentStatus::Employed);
    expect($wrestler->isEmployed())->toBeTrue();
    expect($wrestler->currentEmployment)->not->toBeNull();
    expect($wrestler->currentEmployment->ended_at)->toBeNull();
});

// AFTER: Using custom expectations
test('wrestler employment status - with custom expectations', function () {
    $wrestler = Wrestler::factory()->employed()->create();

    expect($wrestler)->toBeEmployed();
    expect($wrestler->currentEmployment)->toHaveActiveTimelinePeriod();
});

/**
 * PHYSICAL ATTRIBUTES TESTING
 */

// BEFORE: Using traditional assertions
test('wrestler physical attributes - traditional way', function () {
    $wrestler = Wrestler::factory()->make();

    expect($wrestler->height_feet)->toBeBetween(4, 8);
    expect($wrestler->height_inches)->toBeBetween(0, 11);
    expect($wrestler->weight)->toBeBetween(100, 500);
    expect($wrestler->hometown)->toContain(',');
    expect($wrestler->hometown)->not->toContain('Test');
});

// AFTER: Using custom expectations
test('wrestler physical attributes - with custom expectations', function () {
    $wrestler = Wrestler::factory()->make();

    expect($wrestler)->toHaveValidPhysicalAttributes();
    expect($wrestler)->toHaveRealisticHometown();
});

/**
 * NAME VALIDATION TESTING
 */

// BEFORE: Using traditional assertions
test('manager name validation - traditional way', function () {
    $manager = Manager::factory()->make();

    expect($manager->first_name)->toBeString();
    expect($manager->first_name)->not->toBeEmpty();
    expect($manager->last_name)->toBeString();
    expect($manager->last_name)->not->toBeEmpty();
    expect(strlen($manager->first_name))->toBeGreaterThan(2);
    expect(strlen($manager->last_name))->toBeGreaterThan(2);
    expect($manager->first_name)->not->toContain('Test');
    expect($manager->last_name)->not->toContain('Test');
});

// AFTER: Using custom expectations
test('manager name validation - with custom expectations', function () {
    $manager = Manager::factory()->make();

    expect($manager)->toHaveRealisticName();
});

/**
 * TITLE VALIDATION TESTING
 */

// BEFORE: Using traditional assertions
test('title validation - traditional way', function () {
    $title = Title::factory()->make();

    expect($title->name)->toBeString();
    expect($title->name)->not->toBeEmpty();
    expect(strlen($title->name))->toBeGreaterThan(5);
    expect($title->name)->not->toContain('Test');

    $hasWrestlingTerms = str_contains($title->name, 'Championship') ||
                        str_contains($title->name, 'Title') ||
                        str_contains($title->name, 'Belt') ||
                        str_contains($title->name, 'World') ||
                        str_contains($title->name, 'Heavyweight');
    expect($hasWrestlingTerms)->toBeTrue();
});

// AFTER: Using custom expectations
test('title validation - with custom expectations', function () {
    $title = Title::factory()->make();

    expect($title)->toHaveWrestlingTitle();
});

/**
 * SEEDER TESTING
 */

// BEFORE: Using traditional assertions
test('seeder execution - traditional way', function () {
    expect(fn() => Artisan::call('db:seed', ['--class' => 'MatchTypesTableSeeder']))
        ->not->toThrow();

    assertDatabaseCount('match_types', 14);

    $matchTypes = MatchType::all();
    expect($matchTypes->pluck('name')->unique())->toHaveCount(14);
});

// AFTER: Using custom expectations
test('seeder execution - with custom expectations', function () {
    expect('MatchTypesTableSeeder')->toSeedSuccessfully();

    assertDatabaseCount('match_types', 14);

    expect(MatchType::all())->toHaveUniqueNames();
});

/**
 * MODEL STRUCTURE TESTING
 */

// BEFORE: Using traditional assertions
test('model structure - traditional way', function () {
    $wrestler = new Wrestler();

    expect($wrestler->getTable())->toBe('wrestlers');
    expect($wrestler->getFillable())->toEqual(['name', 'hometown', 'height_feet', 'height_inches', 'weight', 'user_id']);
    expect($wrestler->getCasts()['status'])->toBe(EmploymentStatus::class);
    expect($wrestler->query())->toBeInstanceOf(WrestlerBuilder::class);
    expect(class_uses_recursive(Wrestler::class))->toContain(IsEmployable::class);
    expect(class_uses_recursive(Wrestler::class))->toContain(CanJoinStables::class);
});

// AFTER: Using custom expectations
test('model structure - with custom expectations', function () {
    $wrestler = new Wrestler();

    expect($wrestler)->toHaveCorrectTable('wrestlers');
    expect($wrestler)->toHaveCorrectFillable(['name', 'hometown', 'height_feet', 'height_inches', 'weight', 'user_id']);
    expect($wrestler)->toHaveCorrectCasts(['status' => EmploymentStatus::class]);
    expect($wrestler)->toHaveCustomBuilder(WrestlerBuilder::class);
    expect($wrestler)->toUseTrait(IsEmployable::class);
    expect($wrestler)->toUseTrait(CanJoinStables::class);
});

/**
 * RELATIONSHIP TESTING
 */

// BEFORE: Using traditional assertions
test('model relationships - traditional way', function () {
    $wrestler = new Wrestler();

    expect($wrestler->stables())->toBeInstanceOf(BelongsToMany::class);
    expect($wrestler->stables()->getForeignPivotKeyName())->toBe('member_id');
    expect($wrestler->currentStable())->toBeInstanceOf(BelongsToOne::class);
    expect($wrestler->managers())->toBeInstanceOf(BelongsToMany::class);
});

// AFTER: Using custom expectations
test('model relationships - with custom expectations', function () {
    $wrestler = new Wrestler();

    expect($wrestler)->toHaveBelongsToManyRelationship('stables');
    expect($wrestler)->toHaveCorrectForeignKey('stables', 'member_id');
    expect($wrestler)->toHaveRelationship('currentStable', BelongsToOne::class);
    expect($wrestler)->toHaveBelongsToManyRelationship('managers');
});

/**
 * DATE AND TIMELINE TESTING
 */

// BEFORE: Using traditional assertions
test('employment timeline - traditional way', function () {
    $employment = WrestlerEmployment::factory()->make();

    expect($employment->started_at)->toBeInstanceOf(Carbon::class);
    expect($employment->ended_at)->toBeNull();

    if ($employment->ended_at) {
        expect($employment->ended_at->isAfter($employment->started_at))->toBeTrue();
    }
});

// AFTER: Using custom expectations
test('employment timeline - with custom expectations', function () {
    $employment = WrestlerEmployment::factory()->make();

    expect($employment)->toHaveValidDateRange();
    expect($employment)->toHaveActiveTimelinePeriod();
});

/**
 * COLLECTION TESTING
 */

// BEFORE: Using traditional assertions
test('collection uniqueness - traditional way', function () {
    $wrestlers = Wrestler::factory()->count(10)->create();

    expect($wrestlers->pluck('name')->unique())->toHaveCount(10);
    expect($wrestlers->pluck('hometown')->unique())->toHaveCount(10);

    foreach ($wrestlers as $wrestler) {
        expect($wrestler->status)->toBeInstanceOf(EmploymentStatus::class);
    }
});

// AFTER: Using custom expectations
test('collection uniqueness - with custom expectations', function () {
    $wrestlers = Wrestler::factory()->count(10)->create();

    expect($wrestlers)->toHaveUniqueNames();
    expect($wrestlers)->toHaveUniqueValues('hometown');

    foreach ($wrestlers as $wrestler) {
        expect($wrestler)->toHaveValidEmploymentStatus();
    }
});

/**
 * BUSINESS LOGIC TESTING
 */

// BEFORE: Using traditional assertions
test('wrestler availability - traditional way', function () {
    $wrestler = Wrestler::factory()->employed()->create();

    expect($wrestler->isEmployed())->toBeTrue();
    expect($wrestler->isInjured())->toBeFalse();
    expect($wrestler->isSuspended())->toBeFalse();
    expect($wrestler->isRetired())->toBeFalse();
    expect($wrestler->isAvailable())->toBeTrue();
    expect($wrestler->isBookable())->toBeTrue();
});

// AFTER: Using custom expectations
test('wrestler availability - with custom expectations', function () {
    $wrestler = Wrestler::factory()->employed()->create();

    expect($wrestler)->toBeEmployed();
    expect($wrestler)->not->toBeInjured();
    expect($wrestler)->not->toBeSuspended();
    expect($wrestler)->not->toBeRetired();
    expect($wrestler)->toBeAvailable();
    expect($wrestler)->toBeBookable();
});

/**
 * FACTORY TESTING
 */

// BEFORE: Using traditional assertions
test('factory validation - traditional way', function () {
    $factory = Wrestler::factory();

    $wrestler = $factory->make();
    expect($wrestler)->toBeInstanceOf(Wrestler::class);
    expect($wrestler->name)->toBeString();
    expect($wrestler->hometown)->toBeString();

    $createdWrestler = $factory->create();
    expect($createdWrestler->exists)->toBeTrue();
    expect($createdWrestler->id)->toBeGreaterThan(0);
});

// AFTER: Using custom expectations
test('factory validation - with custom expectations', function () {
    $factory = Wrestler::factory();

    expect($factory)->toGenerateRealisticData();
    expect($factory)->toCreateInDatabase();
    expect($factory)->toHaveConsistentStates();
});

/**
 * HELPER FUNCTION USAGE
 */

// BEFORE: Using factory calls directly
test('match creation - traditional way', function () {
    $event = Event::factory()->create();
    $matchType = MatchType::factory()->create();
    $wrestler1 = Wrestler::factory()->employed()->create();
    $wrestler2 = Wrestler::factory()->employed()->create();

    $match = EventMatch::factory()->create([
        'event_id' => $event->id,
        'match_type_id' => $matchType->id,
    ]);

    // Add competitors...
});

// AFTER: Using custom helpers
test('match creation - with custom helpers', function () {
    $eventData = createEventWithMatches(1);
    $bookableWrestlers = [
        createBookableWrestler(),
        createBookableWrestler(),
    ];

    $match = $eventData['matches']->first();

    // Match is ready to use with realistic data
    expect($match)->toExistInDatabase();
});
