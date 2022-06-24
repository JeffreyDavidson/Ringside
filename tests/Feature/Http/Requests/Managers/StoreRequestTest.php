<?php

use App\Http\Requests\Managers\StoreRequest;
use Tests\RequestFactories\ManagerRequestFactory;

test('an administrator is authorized to make this request', function () {
    $this->createRequest(StoreRequest::class)
        ->by(administrator())
        ->assertAuthorized();
});

test('a non administrator is not authorized to make this request', function () {
    $this->createRequest(StoreRequest::class)
        ->by(basicUser())
        ->assertNotAuthorized();
});

test('manager first name is required', function () {
    $this->createRequest(StoreRequest::class)
        ->validate(ManagerRequestFactory::new()->create([
            'first_name' => null,
        ]))
        ->assertFailsValidation(['first_name' => 'required']);
});

test('manager first name must be a string', function () {
    $this->createRequest(StoreRequest::class)
        ->validate(ManagerRequestFactory::new()->create([
            'first_name' => 123,
        ]))
        ->assertFailsValidation(['first_name' => 'string']);
});

test('manager first name must be at least 3 characters', function () {
    $this->createRequest(StoreRequest::class)
        ->validate(ManagerRequestFactory::new()->create([
            'first_name' => 'ab',
        ]))
        ->assertFailsValidation(['first_name' => 'min:3']);
});

test('manager last name is required', function () {
    $this->createRequest(StoreRequest::class)
        ->validate(ManagerRequestFactory::new()->create([
            'last_name' => null,
        ]))
        ->assertFailsValidation(['last_name' => 'required']);
});

test('manager last name must be a string', function () {
    $this->createRequest(StoreRequest::class)
        ->validate(ManagerRequestFactory::new()->create([
            'last_name' => 123,
        ]))
        ->assertFailsValidation(['last_name' => 'string']);
});

test('manager last name must be at least 3 characters', function () {
    $this->createRequest(StoreRequest::class)
        ->validate(ManagerRequestFactory::new()->create([
            'last_name' => 'ab',
        ]))
        ->assertFailsValidation(['last_name' => 'min:3']);
});

test('manager started at is optional', function () {
    $this->createRequest(StoreRequest::class)
        ->validate(ManagerRequestFactory::new()->create([
            'started_at' => null,
        ]))
        ->assertPassesValidation();
});

test('manager started at must be a string if provided', function () {
    $this->createRequest(StoreRequest::class)
        ->validate(ManagerRequestFactory::new()->create([
            'started_at' => 12345,
        ]))
        ->assertFailsValidation(['started_at' => 'string']);
});

test('manager started at must be in the correct date format', function () {
    $this->createRequest(StoreRequest::class)
        ->validate(ManagerRequestFactory::new()->create([
            'started_at' => 'not-a-date',
        ]))
        ->assertFailsValidation(['started_at' => 'date']);
});
