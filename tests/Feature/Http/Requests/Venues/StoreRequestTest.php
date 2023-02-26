<?php

use App\Http\Requests\Venues\StoreRequest;
use App\Models\Venue;
use App\Rules\LetterSpace;
use Tests\RequestFactories\VenueRequestFactory;

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

test('venue name is required', function () {
    $this->createRequest(StoreRequest::class)
        ->validate(VenueRequestFactory::new()->create([
            'name' => null,
        ]))
        ->assertFailsValidation(['name' => 'required']);
});

test('venue name must be a string', function () {
    $this->createRequest(StoreRequest::class)
        ->validate(VenueRequestFactory::new()->create([
            'name' => '123',
        ]))
        ->assertFailsValidation(['name' => LetterSpace::class]);
});

test('venue name can only be letters and spaces', function () {
    $this->createRequest(StoreRequest::class)
        ->validate(VenueRequestFactory::new()->create([
            'name' => 'Invalid!%%# Venue',
        ]))
        ->assertFailsValidation(['name' => LetterSpace::class]);
});

test('venue name must be at least 3 characters', function () {
    $this->createRequest(StoreRequest::class)
        ->validate(VenueRequestFactory::new()->create([
            'name' => 'ab',
        ]))
        ->assertFailsValidation(['name' => 'min:3']);
});

test('venue name must be unique', function () {
    Venue::factory()->create(['name' => 'Example Name Venue']);

    $this->createRequest(StoreRequest::class)
        ->validate(VenueRequestFactory::new()->create([
            'name' => 'Example Name Venue',
        ]))
        ->assertFailsValidation(['name' => 'unique:App\Models\Venue,name']);
});

test('venue street address is required', function () {
    $this->createRequest(StoreRequest::class)
        ->validate(VenueRequestFactory::new()->create([
            'street_address' => null,
        ]))
        ->assertFailsValidation(['street_address' => 'required']);
});

test('venue street address must be a string', function () {
    $this->createRequest(StoreRequest::class)
        ->validate(VenueRequestFactory::new()->create([
            'street_address' => 123,
        ]))
        ->assertFailsValidation(['street_address' => 'string']);
});

test('venue street address must be at least 3 characters', function () {
    $this->createRequest(StoreRequest::class)
        ->validate(VenueRequestFactory::new()->create([
            'street_address' => 'ab',
        ]))
        ->assertFailsValidation(['street_address' => 'min:3']);
});

test('venue city is required', function () {
    $this->createRequest(StoreRequest::class)
        ->validate(VenueRequestFactory::new()->create([
            'city' => null,
        ]))
        ->assertFailsValidation(['city' => 'required']);
});

test('venue city must be a string', function () {
    $this->createRequest(StoreRequest::class)
        ->validate(VenueRequestFactory::new()->create([
            'city' => 123,
        ]))
        ->assertFailsValidation(['city' => 'string']);
});

test('venue city must be at least 3 characters', function () {
    $this->createRequest(StoreRequest::class)
        ->validate(VenueRequestFactory::new()->create([
            'city' => 'ab',
        ]))
        ->assertFailsValidation(['city' => 'min:3']);
});

test('venue state is required', function () {
    $this->createRequest(StoreRequest::class)
        ->validate(VenueRequestFactory::new()->create([
            'state' => null,
        ]))
        ->assertFailsValidation(['state' => 'required']);
});

test('venue state must be a string', function () {
    $this->createRequest(StoreRequest::class)
        ->validate(VenueRequestFactory::new()->create([
            'state' => 123,
        ]))
        ->assertFailsValidation(['state' => 'string']);
});

test('venue zip is required', function () {
    $this->createRequest(StoreRequest::class)
        ->validate(VenueRequestFactory::new()->create([
            'zip' => null,
        ]))
        ->assertFailsValidation(['zip' => 'required']);
});

test('venue zip must be a string', function () {
    $this->createRequest(StoreRequest::class)
        ->validate(VenueRequestFactory::new()->create([
            'zip' => 'abc',
        ]))
        ->assertFailsValidation(['zip' => 'integer']);
});

test('venue zip must be 5 digits long', function () {
    $this->createRequest(StoreRequest::class)
        ->validate(VenueRequestFactory::new()->create([
            'zip' => 1234,
        ]))
        ->assertFailsValidation(['zip' => 'digits:5']);
});
