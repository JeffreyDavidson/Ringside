<?php

use App\Http\Requests\Managers\UpdateRequest;
use App\Models\Manager;
use Illuminate\Support\Carbon;
use Tests\RequestFactories\ManagerRequestFactory;

test('an administrator is authorized to make this request', function () {
    $this->createRequest(UpdateRequest::class)
        ->by(administrator())
        ->assertAuthorized();
});

test('a non administrator is not authorized to make this request', function () {
    $this->createRequest(UpdateRequest::class)
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

test('manager started at is optional', function () {
    $manager = Manager::factory()->create();

    $this->createRequest(UpdateRequest::class)
        ->withParam('manager', $manager)
        ->validate(ManagerRequestFactory::new()->create([
            'started_at' => null,
        ]))
        ->assertPassesValidation();
});

test('manager started at must be a string if provided', function () {
    $manager = Manager::factory()->create();

    $this->createRequest(UpdateRequest::class)
        ->withParam('manager', $manager)
        ->validate(ManagerRequestFactory::new()->create([
            'started_at' => 12345,
        ]))
        ->assertFailsValidation(['started_at' => 'string']);
});

test('manager started at must be in the correct date format', function () {
    $manager = Manager::factory()->create();

    $this->createRequest(UpdateRequest::class)
        ->withParam('manager', $manager)
        ->validate(ManagerRequestFactory::new()->create([
            'started_at' => 'not-a-date-format',
        ]))
        ->assertFailsValidation(['started_at' => 'date']);
});

test('manager started at cannot be changed if employment start date has past', function () {
    $manager = Manager::factory()->available()->create();

    $this->createRequest(UpdateRequest::class)
        ->withParam('manager', $manager)
        ->validate(ManagerRequestFactory::new()->create([
            'started_at' => Carbon::now()->toDateTimeString(),
        ]))
        ->assertFailsValidation(['started_at' => 'app\rules\employmentstartdatecanbechanged']);
});

test('manager started at can be changed if employment start date is in the future', function () {
    $manager = Manager::factory()->withFutureEmployment()->create();

    $this->createRequest(UpdateRequest::class)
        ->withParam('manager', $manager)
        ->validate(ManagerRequestFactory::new()->create([
            'started_at' => Carbon::tomorrow()->toDateString(),
        ]))
        ->assertPassesValidation();
});
