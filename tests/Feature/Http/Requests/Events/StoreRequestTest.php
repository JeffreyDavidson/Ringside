<?php

use App\Http\Requests\Events\StoreRequest;
use App\Models\Event;
use Database\Seeders\MatchTypesTableSeeder;
use Tests\RequestFactories\EventRequestFactory;

beforeEach(fn () => $this->seed(MatchTypesTableSeeder::class));

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

test('event name is required', function () {
    $this->createRequest(StoreRequest::class)
        ->validate(EventRequestFactory::new()->create([
            'name' => null,
        ]))
        ->assertFailsValidation(['name' => 'required']);
});

test('event name must be a string', function () {
    $this->createRequest(StoreRequest::class)
        ->validate(EventRequestFactory::new()->create([
            'name' => 123,
        ]))
        ->assertFailsValidation(['name' => 'string']);
});

test('event name must be at leaset 3 characters', function () {
    $this->createRequest(StoreRequest::class)
        ->validate(EventRequestFactory::new()->create([
            'name' => 'ab',
        ]))
        ->assertFailsValidation(['name' => 'min:3']);
});

test('event name must be unique', function () {
    Event::factory()->create(['name' => 'Example Event Name']);

    $this->createRequest(StoreRequest::class)
        ->validate(EventRequestFactory::new()->create([
            'name' => 'Example Event Name',
        ]))
        ->assertFailsValidation(['name' => 'unique:events,name,NULL,id']);
});

test('event date is optional', function () {
    $this->createRequest(StoreRequest::class)
        ->validate(EventRequestFactory::new()->create([
            'date' => null,
        ]))
        ->assertPassesValidation();
});

test('event date must be a string if provided', function () {
    $this->createRequest(StoreRequest::class)
        ->validate(EventRequestFactory::new()->create([
            'date' => 12345,
        ]))
        ->assertFailsValidation(['date' => 'string']);
});

test('event date must be in the correct date format', function () {
    $this->createRequest(StoreRequest::class)
        ->validate(EventRequestFactory::new()->create([
            'date' => 'not-a-date',
        ]))
        ->assertFailsValidation(['date' => 'date']);
});

test('event venue id is optional', function () {
    $this->createRequest(StoreRequest::class)
        ->validate(EventRequestFactory::new()->create([
            'venue_id' => null,
        ]))
        ->assertPassesValidation();
});

test('event venue id must be an integer if provided', function () {
    $this->createRequest(StoreRequest::class)
        ->validate(EventRequestFactory::new()->create([
            'venue_id' => 'not-an-integer',
        ]))
        ->assertFailsValidation(['venue_id' => 'integer']);
});

test('event venue id must exist if provided', function () {
    $this->createRequest(StoreRequest::class)
        ->validate(EventRequestFactory::new()->create([
            'venue_id' => 1,
        ]))
        ->assertFailsValidation(['venue_id' => 'exists:venues,id']);
});

test('event preview is optional', function () {
    $this->createRequest(StoreRequest::class)
        ->validate(EventRequestFactory::new()->create([
            'preview' => null,
        ]))
        ->assertPassesValidation();
});
