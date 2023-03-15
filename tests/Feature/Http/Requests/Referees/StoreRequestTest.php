<?php

use App\Http\Requests\Referees\StoreRequest;
use App\Rules\LetterSpace;
use function Pest\Laravel\mock;
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

test('referee first name must contain only letters and spaces', function () {
    mock(LetterSpace::class)
        ->shouldReceive('validate')
        ->with('first_name', 1, function ($closure) {
            $closure();
        });

    $this->createRequest(StoreRequest::class)
        ->validate(RefereeRequestFactory::new()->create([
            'first_name' => 'hjkhg*&^HJ',
        ]))
        ->assertFailsValidation(['first_name' => LetterSpace::class]);
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

test('referee last name must contain only letters and spaces', function () {
    mock(LetterSpace::class)
        ->shouldReceive('validate')
        ->with('last_name', 1, function ($closure) {
            $closure();
        });

    $this->createRequest(StoreRequest::class)
        ->validate(RefereeRequestFactory::new()->create([
            'last_name' => 'hjkhg*&^HJ',
        ]))
        ->assertFailsValidation(['last_name' => LetterSpace::class]);
});

test('referee last name must be at least 3 characters', function () {
    $this->createRequest(StoreRequest::class)
        ->validate(RefereeRequestFactory::new()->create([
            'last_name' => 'ab',
        ]))
        ->assertFailsValidation(['last_name' => 'min:3']);
});

test('referee start date is optional', function () {
    $this->createRequest(StoreRequest::class)
        ->validate(RefereeRequestFactory::new()->create([
            'start_date' => null,
        ]))
        ->assertPassesValidation();
});

test('referee start date must be a string if provided', function () {
    $this->createRequest(StoreRequest::class)
        ->validate(RefereeRequestFactory::new()->create([
            'start_date' => 12345,
        ]))
        ->assertFailsValidation(['start_date' => 'string']);
});

test('referee start date must be in the correct date format', function () {
    $this->createRequest(StoreRequest::class)
        ->validate(RefereeRequestFactory::new()->create([
            'start_date' => 'not-a-date',
        ]))
        ->assertFailsValidation(['start_date' => 'date']);
});
