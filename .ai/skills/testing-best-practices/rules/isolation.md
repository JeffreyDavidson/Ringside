# Fakes, Mocks, and Determinism

Tests that depend on actual time, randomness, sleeping, or network calls can fail for reasons unrelated to the code under test. Control all four.

## How to Isolate a Dependency

Fetch `https://laravel.com/framework/docs/mocking` for Laravel's fakes, facade doubles, and fake assertions. Confirm each name before using it.

Identify the dependency, then choose the first applicable option. A framework fake preserves the real code path, while a mock replaces the dependency.

1. Prefer framework fakes for facades such as events, queues, mail, notifications, storage, the HTTP client, time, and sleep; preserve explicitly required project double tools.
2. Use a developer-defined fake implementation of a service if the application provides one.
3. Use a mock for a container-resolved contract only when the real implementation leaves the process or is nondeterministic.
4. Use the real implementation for everything else, including the database.

## Controller Feature-Test Boundaries

Keep registered routing, relevant middleware, binding, FormRequests, policies/gates, internal Actions and database behavior real; read [endpoint tests](endpoint-tests.md#exercise-the-application-http-path). Do not replace the application's own permission or business workflow with a mock and then describe the resulting request as complete controller coverage. A container-resolved adapter may be doubled where it owns external IO or nondeterminism; that is not permission to replace the whole internal workflow. Lower-layer collaborator tests supplement HTTP tests instead of substituting for them.

## Framework Fakes

- Create each fake inside the test that needs it. Do not create fakes in a file-level `beforeEach()`.
- Pass class names to `Event::fake()` and `Queue::fake()` when you know which classes the code dispatches. A fake without class names can hide an unexpected dispatch.
- Use a fake without class names only when the test asserts the complete result, including a call to `assertNothingPushed()`.
- Assert meaningful dispatch or non-dispatch for each fake. Add payload and exact-count checks when those facts protect the contract; one assertion per fake is not a maximum.
- Assert the data of a job or of an event if that data is part of the behavior.
- Use `Exceptions::fake()` to assert that the application reports the correct exception. Do not use `withoutExceptionHandling()`, because it changes the response under test.

Create prerequisite factory records before calling `Event::fake()`. Factories use model events, such as a `creating` hook that generates a UUID, and a fake without class names suppresses those events and can produce an invalid model. Call the fake first only when a factory event is under test, and pass that event's class name.

## Mocking

Use the project's installed and approved double tool. For a Mockery-based suite, use `shouldReceive()` before the action to declare an expectation and `shouldHaveReceived()` after the action for a spy. Use `Mockery::on()` or `withArgs()` when equality cannot state the expected argument. Do not introduce Mockery calls or facade spies into a suite that explicitly requires another tool, such as jasonmccreary/double. Keep each fluent configuration method on its own line and assert the intended interaction or non-interaction.

Import the mock function before you use it: `use function Pest\Laravel\mock;`.

## Outbound HTTP Testing

Call `Http::preventStrayRequests()`. Any request without a matching fake then fails without reaching the network.

Fake the exact endpoint used by each test. Do not call `Http::fake()` without an endpoint because it accepts unexpected requests and can hide defects.

## Time and Randomness

- Freeze the time or move the time in each test that depends on a date, a period, or a timestamp.
- Use the framework helpers `freezeTime()`, `travelTo()`, `travel()`, and `travelBack()`. Do not call `Carbon::setTestNow()`.
- Use `Str::createRandomStringsUsing()` to fix a generated string, if the test asserts an identifier or a slug.
- Use `Sleep::fake()` instead of a real sleep, and assert the sleeps that the code requests.
- Restore the time and the randomness after each test, if the suite does not restore them for every test.

## Database

- Run real queries against the real records in the test database. Do not mock the query builder, because the test then asserts the mock.
- Assert the exact keys of `toArray()` if the shape of the serialized model is a contract. The test then fails when the model exposes a new attribute.
- Test application behavior caused by the schema, such as deleting dependent records through a cascade. Do not test the database engine's cascade implementation.
- Follow the configured database isolation strategy. Consider `LazilyRefreshDatabase` for compatible HTTP suites after checking the installed Laravel version and setup; browser processes and engine-specific tests can require different isolation. Do not switch traits or replace a required database engine merely to speed up a run.
