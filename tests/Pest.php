<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\TestCase;
use Illuminate\Support\Collection;
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

uses(TestCase::class, CreatesApplication::class, RefreshDatabase::class)->in('Feature', 'Unit');
uses(ValidatesRequests::class)->in('Feature/Http/Requests');

uses()->group('managers', 'feature-managers', 'roster', 'feature-roster')
    ->in(
        'Feature/Http/Controllers/Managers',
        'Feature/Http/Livewire/Managers',
        'Feature/Http/Requests/Managers',
        'Feature/Actions/Managers',
        'Feature/Policies/ManagerPolicyTest',
        'Feature/Repositories/ManagerRepositoryTest',
    );
uses()->group('referees', 'feature-referees', 'roster', 'feature-roster')
    ->in(
        'Feature/Actions/Referees',
        'Feature/Http/Controllers/Referees',
        'Feature/Http/Livewire/Referees',
        'Feature/Http/Requests/Referees',
        'Feature/Policies/RefereePolicyTest.php',
        'Feature/Repositories/RefereeRepositoryTest.php',
    );
uses()->group('tagteams', 'feature-tagteams', 'roster', 'feature-roster')->in('Feature/Http/Controllers/TagTeams');
uses()->group('stables', 'feature-stables', 'roster', 'feature-roster')->in('Feature/Http/Controllers/Stables');
uses()->group('venues', 'feature-venues')
    ->in(
        'Feature/Http/Controllers/Venues',
        'Feature/Http/Livewire/Venues',
        'Feature/Http/Requests/Venues',
        'Feature/Actions/Venues',
        'Feature/Policies/VenuePolicyTest',
        'Feature/Repositories/VenueRepositoryTest',
    );
uses()->group('titles', 'feature-titles')->in('Feature/Http/Controllers/Titles');
// uses()->group('events', 'feature-events')->in('Feature/Http/Controllers/Events');
uses()->group('event-matches', 'feature-event-matches')
    ->in('Feature/Http/Controllers/EventMatches', 'Feature/Actions/EventMatches', 'Feature/Http/Requests/EventMatches');
uses()->group('actions')->in('Feature/Actions');
uses()
    ->group('events')
    ->in(
        'Feature/Actions/Events',
        'Feature/Http/Controllers/Events',
        'Feature/Http/Livewire/Events',
        'Feature/Http/Requests/Events',
        'Feature/Http/Repositories/EventRepositoryTest.php'
    );
uses()
    ->group('titles')
    ->in(
        'Feature/Actions/Titles',
        'Feature/Http/Controllers/Titles',
        'Feature/Http/Livewire/Titles',
        'Feature/Http/Requests/Titles',
        'Feature/Http/Repositories/TitleRepositoryTest.php'
    );
uses()->group('wrestlers', 'feature-wrestlers', 'roster', 'feature-roster')
    ->in(
        'Feature/Actions/Wrestlers',
        'Feature/Http/Controllers/Wrestlers',
        'Feature/Http/Livewire/Wrestlers',
        'Feature/Http/Requests/Wrestlers',
        'Feature/Listeners/WrestlerSubscriberTest.php',
        'Feature/Policies/WrestlerPolicyTest.php',
        'Feature/Repositories/WrestlerRepositoryTest.php',
        'Feature/resources/views/wrestlers'
    );

uses()->group('wrestlers', 'unit-wrestlers', 'roster', 'unit-roster')
    ->in(
        'Unit/Builders/WrestlerQueryBuilderTest.php',
        'Unit/Models/WrestlerTest.php',
    );

uses()->group('referees', 'unit-referees', 'roster', 'unit-roster')
    ->in(
        'Unit/Builders/RefereeQueryBuilderTest.php',
        'Unit/Models/RefereeTest.php',
    );

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

expect()->extend('collectionHas', function ($entity) {
    if (is_array($entity) || $entity instanceof Collection) {
        foreach ($entity as $test) {
            $this->value->assertContains($this, $test);
        }

        return $this;
    }

    expect($this->value)->contains($entity)->toBeTrue();

    return $this;
});

expect()->extend('collectionDoesntHave', function ($entity) {
    if (is_array($entity) || $entity instanceof Collection) {
        foreach ($entity as $test) {
            $this->value->assertNotContains($this, $test);
        }

        return $this;
    }

    expect($this->value)->contains($entity)->toBeFalse();

    return $this;
});

expect()->extend('usesTrait', function ($trait) {
    expect(class_uses($this->value))->toContain($trait);

    return $this;
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
