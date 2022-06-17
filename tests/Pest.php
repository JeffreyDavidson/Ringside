<?php

use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Foundation\Testing\TestCase;
use function PHPUnit\Framework\assertContains;
use function PHPUnit\Framework\assertNotContains;
use Tests\CreatesApplication;
use Tests\ValidatesRequests;

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
|
| The closure you provide to your test functions is always bound to a specific PHPUnit test
| case class. By default, that class is "PHPUnit\Framework\TestCase". Of course, you may
| need to change it using the "uses()" function to bind a different classes or traits.
|
*/

uses(TestCase::class, CreatesApplication::class, LazilyRefreshDatabase::class)->in('Feature', 'Unit');
uses(ValidatesRequests::class)->in('Integration/Http/Requests');

uses()->group('wrestlers', 'feature-wrestlers', 'roster', 'feature-roster')->in('Feature/Http/Controllers/Wrestlers');
uses()->group('managers', 'feature-managers', 'roster', 'feature-roster')->in('Feature/Http/Controllers/Managers');
uses()->group('referees', 'feature-referees', 'roster', 'feature-roster')->in('Feature/Http/Controllers/Referees');
uses()->group('tagteams', 'feature-tagteams', 'roster', 'feature-roster')->in('Feature/Http/Controllers/TagTeams');
uses()->group('stables', 'feature-stables', 'roster', 'feature-roster')->in('Feature/Http/Controllers/Stables');
uses()->group('venues', 'feature-venues')->in('Feature/Http/Controllers/Venues');
uses()->group('titles', 'feature-titles')->in('Feature/Http/Controllers/Titles');
uses()->group('events', 'feature-events')->in('Feature/Http/Controllers/Events');
uses()->group('event-matches', 'feature-event-matches')->in('Feature/Http/Controllers/EventMatches');

beforeEach(function () {
    TestResponse::macro('data', fn ($key) => $this->original->getData()[$key]);
    $dropViews = true;
});

/*
|--------------------------------------------------------------------------
| Expectations
|--------------------------------------------------------------------------
|
| When you're writing tests, you often need to check that values meet certain conditions. The
| "expect()" function gives you access to a set of "expectations" methods that you can use
| to assert different things. Of course, you may extend the Expectation API at any time.
|
*/

expect()->extend('toBeOne', function () {
    return $this->toBe(1);
});

expect()->extend('collectionHas', function ($collection, $entity) {
    if (is_array($entity) || $entity instanceof Collection) {
        foreach ($entity as $test) {
            assertContains($collection, $test);
        }

        return $this;
    }

    expect($collection->contains($entity))->toBeTrue();

    return $this;
});

expect()->extend('collectionDoesntHave', function ($collection, $entity) {
    if (is_array($entity) || $entity instanceof Collection) {
        foreach ($entity as $test) {
            assertNotContains($collection, $test);
        }

        return $this;
    }

    expect($collection->contains($entity))->toBeFalse();

    return $this;
});

expect()->extend('assertUsesTrait', function ($trait) {
    $this->assertContains($trait, class_uses($this));
});

/*
|--------------------------------------------------------------------------
| Functions
|--------------------------------------------------------------------------
|
| While Pest is very powerful out-of-the-box, you may have some testing code specific to your
| project that you don't want to repeat in every file. Here you can also expose helpers as
| global functions to help you to reduce the number of lines of code in your test files.
|
*/

function administrator()
{
    return User::factory()->administrator()->create();
}

function basicUser()
{
    return User::factory()->basicUser()->create();
}
