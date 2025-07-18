<?php

declare(strict_types=1);

use App\Models\Users\User;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\TestCase;
use Illuminate\Support\Collection;
use Tests\DuskTestCase;

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

uses(
    DuskTestCase::class,
    DatabaseMigrations::class,
)->in('Browser');

uses(TestCase::class, RefreshDatabase::class)
    ->in('Feature', 'Integration', 'Unit');

pest()
    ->in('Feature')
    ->beforeEach(fn () => $this->withoutVite());

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
| Custom Expectations
|--------------------------------------------------------------------------
|
| Load custom expectations for domain-specific testing. These expectations
| provide more expressive and maintainable assertions for wrestling business
| logic, database operations, and model structure validation.
|
*/

require_once __DIR__.'/Expectations/WrestlingExpectations.php';
require_once __DIR__.'/Expectations/DatabaseExpectations.php';
require_once __DIR__.'/Expectations/ModelExpectations.php';

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

/*
|--------------------------------------------------------------------------
| Custom Test Helpers
|--------------------------------------------------------------------------
|
| Load custom helper functions for common testing scenarios. These helpers
| provide convenient methods for creating test data, setting up scenarios,
| and performing repetitive test operations.
|
*/

require_once __DIR__.'/Helpers/TestHelpers.php';
