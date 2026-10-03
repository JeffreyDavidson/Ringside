<?php

declare(strict_types=1);

use App\Enums\Users\UserStatus;
use App\Models\Users\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\TestCase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Translation\PotentiallyTranslatedString;
use Illuminate\Translation\Translator;
use Pest\Browser\Api\AwaitableWebpage;
use Pest\Browser\Api\PendingAwaitablePage;

use function Pest\Laravel\freezeTime;
use function Pest\Laravel\withoutVite;

pest()->tia()->baselined();

pest()->tia()->watch([
    'phpunit.xml' => 'tests',
]);

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

pest()->extend(TestCase::class)->use(RefreshDatabase::class)->in('Browser');

pest()
    ->extend(TestCase::class)
    ->use(RefreshDatabase::class)
    ->beforeEach(function () {
        freezeTime();
        reverseUnorderedSelectsWhenRequested();
    })
    ->in('Integration');

pest()
    ->beforeEach(function () {
        Relation::requireMorphMap(false);
    })
    ->afterEach(function () {
        Relation::requireMorphMap();
    })
    ->in('Integration/Models/Concerns');

pest()
    ->extend(TestCase::class)
    ->use(RefreshDatabase::class)
    ->beforeEach(function () {
        withoutVite();
        freezeTime();
        reverseUnorderedSelectsWhenRequested();
    })
    ->in('Feature');

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

expect()->extend('toBeOne', fn () => $this->toBe(1));

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

function administrator(): User
{
    return User::factory()->administrator()->create(['status' => UserStatus::Active]);
}

function basicUser(): User
{
    return User::factory()->basicUser()->create(['status' => UserStatus::Active]);
}

/**
 * Adapt a test observer to Laravel's validation failure callback contract.
 */
function validationFailureCallback(Closure $observer): Closure
{
    return function (string $message) use ($observer): PotentiallyTranslatedString {
        $observer($message);

        return new PotentiallyTranslatedString($message, app(Translator::class));
    };
}

/**
 * Opt-in guard against tests that depend on the order of rows a query never ordered: with REVERSE_UNORDERED_SELECTS=1
 * SQLite returns every unordered result in reverse, so such a test fails instead of passing by accident. See
 * docs/workflows/ci-cd.md.
 */
function reverseUnorderedSelectsWhenRequested(): void
{
    if (getenv('REVERSE_UNORDERED_SELECTS') !== '1' || ! runsOnDriver('sqlite')) {
        return;
    }

    DB::statement('PRAGMA reverse_unordered_selects = ON');
}

/**
 * Return a date that must exist for the tested state.
 */
function requiredDate(?Carbon $date): Carbon
{
    return $date ?? throw new RuntimeException('Expected the model date to be present.');
}

/**
 * Return a model that must exist for the tested state.
 *
 * @template TModel of Model
 *
 * @param  TModel|null  $model
 * @return TModel
 */
function requiredModel(?Model $model): Model
{
    return $model ?? throw new RuntimeException('Expected the model relationship to be present.');
}

/**
 * Reload a separate model instance that must still exist.
 *
 * @template TModel of Model
 *
 * @param  TModel|null  $model
 * @return TModel
 */
function freshModel(?Model $model): Model
{
    if ($model === null) {
        throw new RuntimeException('Expected the model to be present before reloading it.');
    }

    $freshModel = $model->fresh();
    if ($freshModel === null) {
        throw new RuntimeException('Expected the model to exist when reloading it.');
    }

    return $freshModel;
}

/**
 * Return a reflection type that must exist for the tested declaration.
 */
function requiredReflectionType(?ReflectionType $type): ReflectionType
{
    return $type ?? throw new RuntimeException('Expected the reflected declaration to have a type.');
}

/**
 * Wait until a JavaScript condition holds in the browser (at most five seconds), then assert it.
 *
 * Use this instead of fixed sleeps so a browser test waits exactly as long as the page needs.
 */
function waitForScript(AwaitableWebpage|PendingAwaitablePage $page, string $condition): void
{
    $page->assertScript(<<<JS
        () => new Promise((resolve) => {
            const deadline = Date.now() + 5000;
            const holds = () => {
                try {
                    return Boolean({$condition});
                } catch {
                    return false;
                }
            };
            const check = () => {
                if (holds()) {
                    resolve(true);
                } else if (Date.now() > deadline) {
                    resolve(false);
                } else {
                    setTimeout(check, 20);
                }
            };
            check();
        })
        JS);
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
require_once __DIR__.'/Helpers/ReflectionHelpers.php';
require_once __DIR__.'/Helpers/FakerHelpers.php';
