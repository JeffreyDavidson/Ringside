# How to Find Test Framework Features

Pest adds features faster than this skill can list them. Find an existing feature before implementing the behavior by hand.

- When Boost `search-docs` is available, give it the capability you need and scope to the relevant packages. Otherwise use official version-matched documentation or installed source.
- Fetch `https://pestphp.com/llms.txt` for the complete feature list and additions in each release.
- If a search returns no results, verify the installed version and source before concluding the feature is unavailable. Do not invent an API.

Search for a feature in this table before you write the code by hand.

| Work that you need | Term to search for |
| --- | --- |
| Run one test with many input values | datasets, bound datasets |
| Assert over many values or over a collection | higher-order expectations |
| Remove the same setup from each test in a file | hooks, higher-order tests |
| Apply a convention to the complete codebase | architecture testing |
| Measure if the suite finds a defect | mutation testing |
| Find code with no types | type coverage |
| Reduce the time of a slow suite | parallel, profiling |
| Split the suite across CI jobs | sharding, `--update-shards` |
| Run only the tests that a change affects | Test Impact Analysis, `--tia` |
| Assert that a value has a known format | validation expectations |
| Run one test while you debug | filtering, `--bail`, `--dirty` |

## Built-in Laravel Assertion Methods

Laravel provides assertions for each part of the framework. Fetch `https://laravel.com/framework/docs/testing` for the complete list, and search for an assertion before building a check by hand. Examples include `assertDatabaseHas()`, `assertModelExists()`, `assertSoftDeleted()`, response assertions such as `assertRedirectToRoute()` and `assertJsonPath()`, and fake assertions such as `Queue::assertPushed()` and `Notification::assertSentTo()`.

A hand-built check fails with `false is not true`, which identifies nothing. A framework assertion names the incorrect table, value, or response, so the failure indicates what to fix.

```php
// The failure says that false is not true. Instead of this...
expect(User::where('email', 'taylor@laravel.com')->exists())->toBeTrue();

// Use this... the failure names the table and the attributes that it did not find...
use function Pest\Laravel\assertDatabaseHas;

assertDatabaseHas('users', ['email' => 'taylor@laravel.com']);
```
