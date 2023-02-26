<?php

use App\Http\Requests\Venues\UpdateRequest;
use App\Models\Activation;
use App\Models\Venue;
use App\Rules\ActivationStartDateCanBeChanged;
use Illuminate\Support\Carbon;
use Tests\RequestFactories\VenueRequestFactory;

test('an administrator is authorized to make this request', function () {
    $venue = Venue::factory()->create();

    $this->createRequest(UpdateRequest::class)
        ->withParam('venue', $venue)
        ->by(administrator())
        ->assertAuthorized();
});

test('a non administrator is not authorized to make this request', function () {
    $venue = Venue::factory()->create();

    $this->createRequest(UpdateRequest::class)
        ->withParam('venue', $venue)
        ->by(basicUser())
        ->assertNotAuthorized();
});

test('venue name is required', function () {
    $venue = Venue::factory()->create();

    $this->createRequest(UpdateRequest::class)
        ->withParam('venue', $venue)
        ->validate(VenueRequestFactory::new()->create([
            'name' => null,
        ]))
        ->assertFailsValidation(['name' => 'required']);
});

test('venue name must be a string', function () {
    $venue = Venue::factory()->create();

    $this->createRequest(UpdateRequest::class)
        ->withParam('venue', $venue)
        ->validate(VenueRequestFactory::new()->create([
            'name' => 123,
        ]))
        ->assertFailsValidation(['name' => 'string']);
});

test('venue name can only be letters and spaces', function () {
    $venue = Venue::factory()->create();

    $this->createRequest(UpdateRequest::class)
        ->withParam('venue', $venue)
        ->validate(VenueRequestFactory::new()->create([
            'name' => 'Invalid!%%# Name Venue',
        ]))
        ->assertFailsValidation(['name' => LetterSpace::class]);
});

test('venue name must be at least 3 characters', function () {
    $venue = Venue::factory()->create();

    $this->createRequest(UpdateRequest::class)
        ->withParam('venue', $venue)
        ->validate(VenueRequestFactory::new()->create([
            'name' => 'ab',
        ]))
        ->assertFailsValidation(['name' => 'min:3']);
});

test('venue name must be unique', function () {
    $venueA = Venue::factory()->create();
    Venue::factory()->create(['name' => 'Example Name Venue']);

    $this->createRequest(UpdateRequest::class)
        ->withParam('venue', $venueA)
        ->validate(VenueRequestFactory::new()->create([
            'name' => 'Example Name Venue',
        ]))
        ->assertFailsValidation(['name' => 'unique:App\Models\Venue,name']);
});

test('venue street address date is required', function () {
    $venue = Venue::factory()->create();

    $this->createRequest(UpdateRequest::class)
        ->withParam('venue', $venue)
        ->validate(VenueRequestFactory::new()->create([
            'street_address' => null,
        ]))
        ->assertFailsValidation(['street_address' => 'required']);
});

test('venue street address must be a string', function () {
    $venue = Venue::factory()->create();

    $this->createRequest(UpdateRequest::class)
        ->withParam('venue', $venue)
        ->validate(VenueRequestFactory::new()->create([
            'street_address' => 123,
        ]))
        ->assertFailsValidation(['street_address' => 'string']);
});

test('venue street address must be at least 3 characters', function () {
    $venue = Venue::factory()->create();

    $this->createRequest(UpdateRequest::class)
        ->withParam('venue', $venue)
        ->validate(VenueRequestFactory::new()->create([
            'street_address' => 'ab',
        ]))
        ->assertFailsValidation(['street_address' => 'min:3']);
});

test('venue city is required', function () {
    $venue = Venue::factory()->create();

    $this->createRequest(UpdateRequest::class)
        ->withParam('venue', $venue)
        ->validate(VenueRequestFactory::new()->create([
            'city' => null,
        ]))
        ->assertFailsValidation(['city' => 'required']);
});

test('venue city must be a string', function () {
    $venue = Venue::factory()->create();

    $this->createRequest(UpdateRequest::class)
        ->withParam('venue', $venue)
        ->validate(VenueRequestFactory::new()->create([
            'city' => 123,
        ]))
        ->assertFailsValidation(['city' => 'string']);
});

test('venue city must be at least 3 characters', function () {
    $venue = Venue::factory()->create();

    $this->createRequest(UpdateRequest::class)
        ->withParam('venue', $venue)
        ->validate(VenueRequestFactory::new()->create([
            'city' => 'ab',
        ]))
        ->assertFailsValidation(['city' => 'min:3']);
});

test('venue state is required', function () {
    $venue = Venue::factory()->create();

    $this->createRequest(UpdateRequest::class)
        ->withParam('venue', $venue)
        ->validate(VenueRequestFactory::new()->create([
            'state' => null,
        ]))
        ->assertFailsValidation(['state' => 'required']);
});

test('venue state must be a string', function () {
    $venue = Venue::factory()->create();

    $this->createRequest(UpdateRequest::class)
        ->withParam('venue', $venue)
        ->validate(VenueRequestFactory::new()->create([
            'state' => 123,
        ]))
        ->assertFailsValidation(['state' => 'string']);
});

test('venue zip is required', function () {
    $venue = Venue::factory()->create();

    $this->createRequest(UpdateRequest::class)
        ->withParam('venue', $venue)
        ->validate(VenueRequestFactory::new()->create([
            'zip' => null,
        ]))
        ->assertFailsValidation(['zip' => 'required']);
});

test('venue zip must be a string', function () {
    $venue = Venue::factory()->create();

    $this->createRequest(UpdateRequest::class)
        ->withParam('venue', $venue)
        ->validate(VenueRequestFactory::new()->create([
            'zip' => 'abc',
        ]))
        ->assertFailsValidation(['zip' => 'integer']);
});

test('venue zip must be 5 digits long', function () {
    $venue = Venue::factory()->create();

    $this->createRequest(UpdateRequest::class)
        ->withParam('venue', $venue)
        ->validate(VenueRequestFactory::new()->create([
            'zip' => 1234,
        ]))
        ->assertFailsValidation(['zip' => 'digits:5']);
});
