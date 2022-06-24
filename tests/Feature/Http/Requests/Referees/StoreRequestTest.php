<?php

use App\Http\Requests\Referees\StoreRequest;
use Tests\RequestFactories\RefereeRequestFactory;

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

test('referee first name is required', function () {
    $this->createRequest(StoreRequest::class)
        ->validate(RefereeRequestFactory::new()->create([
            'first_name' => null,
        ]))
        ->assertFailsValidation(['first_name' => 'required']);
});

test('referee first name must be a string', function () {
    $this->createRequest(StoreRequest::class)
        ->validate(RefereeRequestFactory::new()->create([
            'first_name' => 123,
        ]))
        ->assertFailsValidation(['first_name' => 'string']);
});

test('referee first name must be at least 3 characters', function () {
    $this->createRequest(StoreRequest::class)
        ->validate(RefereeRequestFactory::new()->create([
            'first_name' => 'ab',
        ]))
        ->assertFailsValidation(['first_name' => 'min:3']);
});

test('referee last name is required', function () {
    $this->createRequest(StoreRequest::class)
        ->validate(RefereeRequestFactory::new()->create([
            'last_name' => null,
        ]))
        ->assertFailsValidation(['last_name' => 'required']);
});

test('referee last name must be a string', function () {
    $this->createRequest(StoreRequest::class)
        ->validate(RefereeRequestFactory::new()->create([
            'last_name' => 123,
        ]))
        ->assertFailsValidation(['last_name' => 'string']);
});

test('referee last name must be at least 3 characters', function () {
    $this->createRequest(StoreRequest::class)
        ->validate(RefereeRequestFactory::new()->create([
            'last_name' => 'ab',
        ]))
        ->assertFailsValidation(['last_name' => 'min:3']);
});

test('referee started at is optional', function () {
    $this->createRequest(StoreRequest::class)
        ->validate(RefereeRequestFactory::new()->create([
            'started_at' => null,
        ]))
        ->assertPassesValidation();
});

test('referee started at must be a string if provided', function () {
    $this->createRequest(StoreRequest::class)
        ->validate(RefereeRequestFactory::new()->create([
            'started_at' => 12345,
        ]))
        ->assertFailsValidation(['started_at' => 'string']);
});

test('referee started at must be in the correct date format', function () {
    $this->createRequest(StoreRequest::class)
        ->validate(RefereeRequestFactory::new()->create([
            'started_at' => 'not-a-date',
        ]))
        ->assertFailsValidation(['started_at' => 'date']);
});
