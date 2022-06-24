<?php

use App\Http\Requests\Events\UpdateRequest;
use App\Models\Event;
use Illuminate\Support\Carbon;
use Tests\RequestFactories\EventRequestFactory;

test('an administrator is authorized to make this request', function () {
    $event = Event::factory()->scheduled()->create();

    $this->createRequest(UpdateRequest::class)
        ->withParam('event', $event)
        ->by(administrator())
        ->assertAuthorized();
});

test('a non administrator is not authorized to make this request', function () {
    $event = Event::factory()->scheduled()->create();

    $this->createRequest(UpdateRequest::class)
        ->withParam('event', $event)
        ->by(basicUser())
        ->assertNotAuthorized();
});

test('event name is required', function () {
    $event = Event::factory()->create();

    $this->createRequest(UpdateRequest::class)
        ->withParam('event', $event)
        ->validate(EventRequestFactory::new()->create([
            'name' => null,
        ]))
        ->assertFailsValidation(['name' => 'required']);
});

test('event name must be a string', function () {
    $event = Event::factory()->create();

    $this->createRequest(UpdateRequest::class)
        ->withParam('event', $event)
        ->validate(EventRequestFactory::new()->create([
            'name' => 123,
        ]))
        ->assertFailsValidation(['name' => 'string']);
});

test('event name must be a at least characters', function () {
    $event = Event::factory()->create();

    $this->createRequest(UpdateRequest::class)
        ->withParam('event', $event)
        ->validate(EventRequestFactory::new()->create([
            'name' => 'ab',
        ]))
        ->assertFailsValidation(['name' => 'min:3']);
});

test('event name must be unique', function () {
    $eventA = Event::factory()->create();
    $eventB = Event::factory()->create(['name' => 'Example Event']);

    $this->createRequest(UpdateRequest::class)
        ->withParam('event', $eventA)
        ->validate(EventRequestFactory::new()->create([
            'name' => 'Example Event',
        ]))
        ->assertFailsValidation(['name' => 'unique:events,NULL,1,id']);
});

test('event date is optional', function () {
    $event = Event::factory()->create();

    $this->createRequest(UpdateRequest::class)
        ->withParam('event', $event)
        ->validate(EventRequestFactory::new()->create([
            'date' => null,
        ]))
        ->assertPassesValidation();
});

test('event date must be a string if provided', function () {
    $event = Event::factory()->create();

    $this->createRequest(UpdateRequest::class)
        ->withParam('event', $event)
        ->validate(EventRequestFactory::new()->create([
            'date' => 12345,
        ]))
        ->assertFailsValidation(['date' => 'string']);
});

test('event date must be in the correct date format', function () {
    $event = Event::factory()->create();

    $this->createRequest(UpdateRequest::class)
        ->withParam('event', $event)
        ->validate(EventRequestFactory::new()->create([
            'date' => 'not=-a-date-format',
        ]))
        ->assertFailsValidation(['date' => 'date']);
});

test('event date cannot be changed if event date has past', function () {
    $event = Event::factory()->scheduledOn('2021-01-01')->create();

    $this->createRequest(UpdateRequest::class)
        ->withParam('event', $event)
        ->validate(EventRequestFactory::new()->create([
            'date' => '2021-02-01',
        ]))
        ->assertFailsValidation(['date' => 'app\rules\eventdatecanbechanged']);
});

test('event date can be changed if activation start date is in the future', function () {
    $event = Event::factory()->scheduledOn(Carbon::parse('+2 weeks'))->create();

    $this->createRequest(UpdateRequest::class)
        ->withParam('event', $event)
        ->validate(EventRequestFactory::new()->create([
            'date' => Carbon::tomorrow()->toDateString(),
        ]))
        ->assertPassesValidation();
});

test('event venue id is optional', function () {
    $event = Event::factory()->create();

    $this->createRequest(UpdateRequest::class)
        ->withParam('event', $event)
        ->validate(EventRequestFactory::new()->create([
            'venue_id' => null,
        ]))
        ->assertPassesValidation();
});

test('event venue id must be an integer if provided', function () {
    $event = Event::factory()->create();

    $this->createRequest(UpdateRequest::class)
        ->withParam('event', $event)
        ->validate(EventRequestFactory::new()->create([
            'venue_id' => 'not-an-integer',
        ]))
        ->assertFailsValidation(['venue_id' => 'integer']);
});

test('event venue id must exist', function () {
    $event = Event::factory()->create();

    $this->createRequest(UpdateRequest::class)
        ->withParam('event', $event)
        ->validate(EventRequestFactory::new()->create([
            'venue_id' => 1,
        ]))
        ->assertFailsValidation(['venue_id' => 'exists']);
});

test('event preview is optional', function () {
    $event = Event::factory()->create();

    $this->createRequest(UpdateRequest::class)
        ->withParam('event', $event)
        ->validate(EventRequestFactory::new()->create([
            'preview' => null,
        ]))
        ->assertPassesValidation();
});

test('event preview must be a string if provided', function () {
    $event = Event::factory()->create();

    $this->createRequest(UpdateRequest::class)
        ->withParam('event', $event)
        ->validate(EventRequestFactory::new()->create([
            'preview' => 1234,
        ]))
        ->assertFailsValidation(['preview' => 'string']);
});
