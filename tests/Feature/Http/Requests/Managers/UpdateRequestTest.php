<?php

use App\Http\Requests\Managers\UpdateRequest;
use App\Models\Manager;
use App\Rules\EmploymentStartDateCanBeChanged;
use Illuminate\Support\Carbon;
use Tests\RequestFactories\ManagerRequestFactory;

test('an administrator is authorized to make this request', function () {
    $manager = Manager::factory()->create();

    $this->createRequest(UpdateRequest::class)
        ->withParam('manager', $manager)
        ->by(administrator())
        ->assertAuthorized();
});

test('a non administrator is not authorized to make this request', function () {
    $manager = Manager::factory()->create();

    $this->createRequest(UpdateRequest::class)
        ->withParam('manager', $manager)
        ->by(basicUser())
        ->assertNotAuthorized();
});

test('manager first name is required', function () {
    $manager = Manager::factory()->create();

    $this->createRequest(UpdateRequest::class)
        ->withParam('manager', $manager)
        ->validate(ManagerRequestFactory::new()->create([
            'first_name' => null,
        ]))
        ->assertFailsValidation(['first_name' => 'required']);
});

test('manager first name must be a string', function () {
    $manager = Manager::factory()->create();

    $this->createRequest(UpdateRequest::class)
        ->withParam('manager', $manager)
        ->validate(ManagerRequestFactory::new()->create([
            'first_name' => 12345,
        ]))
        ->assertFailsValidation(['first_name' => 'string']);
});

test('manager first name must be at least 3 characters', function () {
    $manager = Manager::factory()->create();

    $this->createRequest(UpdateRequest::class)
        ->withParam('manager', $manager)
        ->validate(ManagerRequestFactory::new()->create([
            'first_name' => 'ab',
        ]))
        ->assertFailsValidation(['first_name' => 'min:3']);
});

test('manager last name is required', function () {
    $manager = Manager::factory()->create();

    $this->createRequest(UpdateRequest::class)
        ->withParam('manager', $manager)
        ->validate(ManagerRequestFactory::new()->create([
            'last_name' => null,
        ]))
        ->assertFailsValidation(['last_name' => 'required']);
});

test('manager last name must be a string', function () {
    $manager = Manager::factory()->create();

    $this->createRequest(UpdateRequest::class)
        ->withParam('manager', $manager)
        ->validate(ManagerRequestFactory::new()->create([
            'last_name' => 12345,
        ]))
        ->assertFailsValidation(['last_name' => 'string']);
});

test('manager last name must be at least 3 characters', function () {
    $manager = Manager::factory()->create();

    $this->createRequest(UpdateRequest::class)
        ->withParam('manager', $manager)
        ->validate(ManagerRequestFactory::new()->create([
            'last_name' => 'ab',
        ]))
        ->assertFailsValidation(['last_name' => 'min:3']);
});

test('manager start date is optional', function () {
    $manager = Manager::factory()->create();

    $this->createRequest(UpdateRequest::class)
        ->withParam('manager', $manager)
        ->validate(ManagerRequestFactory::new()->create([
            'start_date' => null,
        ]))
        ->assertPassesValidation();
});

test('manager start date must be a string if provided', function () {
    $manager = Manager::factory()->create();

    $this->createRequest(UpdateRequest::class)
        ->withParam('manager', $manager)
        ->validate(ManagerRequestFactory::new()->create([
            'start_date' => 12345,
        ]))
        ->assertFailsValidation(['start_date' => 'string']);
});

test('manager start date must be in the correct date format', function () {
    $manager = Manager::factory()->create();

    $this->createRequest(UpdateRequest::class)
        ->withParam('manager', $manager)
        ->validate(ManagerRequestFactory::new()->create([
            'start_date' => 'not-a-date-format',
        ]))
        ->assertFailsValidation(['start_date' => 'date']);
});

test('manager start date cannot be changed if employment start date has past', function () {
    $manager = Manager::factory()->available()->create();

    $this->createRequest(UpdateRequest::class)
        ->withParam('manager', $manager)
        ->validate(ManagerRequestFactory::new()->create([
            'start_date' => Carbon::now()->toDateTimeString(),
        ]))
        ->assertFailsValidation(['start_date' => EmploymentStartDateCanBeChanged::class]);
});

test('manager start date can be changed if employment start date is in the future', function () {
    $manager = Manager::factory()->withFutureEmployment()->create();

    $this->createRequest(UpdateRequest::class)
        ->withParam('manager', $manager)
        ->validate(ManagerRequestFactory::new()->create([
            'start_date' => Carbon::tomorrow()->toDateString(),
        ]))
        ->assertPassesValidation();
});
