<?php

use App\Http\Requests\Wrestlers\StoreRequest;
use App\Models\Wrestler;
use Tests\RequestFactories\WrestlerRequestFactory;

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

test('wrestler name is required', function () {
    $this->createRequest(StoreRequest::class)
        ->validate(WrestlerRequestFactory::new()->create([
            'name' => null,
        ]))
        ->assertFailsValidation(['name' => 'required']);
});

test('wrestler name must be a string', function () {
    $this->createRequest(StoreRequest::class)
        ->validate(WrestlerRequestFactory::new()->create([
            'name' => 123,
        ]))
        ->assertFailsValidation(['name' => 'string']);
});

test('wrestler name must be at least 3 characters', function () {
    $this->createRequest(StoreRequest::class)
        ->validate(WrestlerRequestFactory::new()->create([
            'name' => 'ab',
        ]))
        ->assertFailsValidation(['name' => 'min:3']);
});

test('wrestler name must be unique', function () {
    Wrestler::factory()->create(['name' => 'Example Wrestler Name']);

    $this->createRequest(StoreRequest::class)
        ->validate(WrestlerRequestFactory::new()->create([
            'name' => 'Example Wrestler Name',
        ]))
        ->assertFailsValidation(['name' => 'unique:wrestlers,NULL,NULL,id']);
});

test('wrestler height in feet is required', function () {
    $this->createRequest(StoreRequest::class)
        ->validate(WrestlerRequestFactory::new()->create([
            'feet' => null,
        ]))
        ->assertFailsValidation(['feet' => 'required']);
});

test('wrestler height in feet must be an integer', function () {
    $this->createRequest(StoreRequest::class)
        ->validate(WrestlerRequestFactory::new()->create([
            'feet' => 'not-an-integer',
        ]))
        ->assertFailsValidation(['feet' => 'integer']);
});

test('wrestler height in inches is required', function () {
    $this->createRequest(StoreRequest::class)
        ->validate(WrestlerRequestFactory::new()->create([
            'inches' => null,
        ]))
        ->assertFailsValidation(['inches' => 'required']);
});

test('wrestler height in inches must be an integer', function () {
    $this->createRequest(StoreRequest::class)
        ->validate(WrestlerRequestFactory::new()->create([
            'inches' => 'not-an-integer',
        ]))
        ->assertFailsValidation(['inches' => 'integer']);
});

test('wrestler height in inches has a max of 11', function () {
    $this->createRequest(StoreRequest::class)
        ->validate(WrestlerRequestFactory::new()->create([
            'inches' => 12,
        ]))
        ->assertFailsValidation(['inches' => 'max:11']);
});

test('wrestler weight is required', function () {
    $this->createRequest(StoreRequest::class)
        ->validate(WrestlerRequestFactory::new()->create([
            'weight' => null,
        ]))
        ->assertFailsValidation(['weight' => 'required']);
});

test('wrestler weight must be an integer', function () {
    $this->createRequest(StoreRequest::class)
        ->validate(WrestlerRequestFactory::new()->create([
            'weight' => 'not-an-integer',
        ]))
        ->assertFailsValidation(['weight' => 'integer']);
});

test('wrestler hometown is required', function () {
    $this->createRequest(StoreRequest::class)
        ->validate(WrestlerRequestFactory::new()->create([
            'hometown' => null,
        ]))
        ->assertFailsValidation(['hometown' => 'required']);
});

test('wrestler hometown must be a string', function () {
    $this->createRequest(StoreRequest::class)
        ->validate(WrestlerRequestFactory::new()->create([
            'hometown' => 12345,
        ]))
        ->assertFailsValidation(['hometown' => 'string']);
});

test('wrestler signature move is optional', function () {
    $this->createRequest(StoreRequest::class)
        ->validate(WrestlerRequestFactory::new()->create([
            'signature_move' => null,
        ]))
        ->assertPassesValidation();
});

test('wrestler signature move must be a string if provided', function () {
    $this->createRequest(StoreRequest::class)
        ->validate(WrestlerRequestFactory::new()->create([
            'signature_move' => 12345,
        ]))
        ->assertFailsValidation(['signature_move' => 'string']);
});

test('wrestler started at is optional', function () {
    $this->createRequest(StoreRequest::class)
        ->validate(WrestlerRequestFactory::new()->create([
            'started_at' => null,
        ]))
        ->assertPassesValidation();
});

test('wrestler started at must be a string if provided', function () {
    $this->createRequest(StoreRequest::class)
        ->validate(WrestlerRequestFactory::new()->create([
            'started_at' => 12345,
        ]))
        ->assertFailsValidation(['started_at' => 'string']);
});

test('wrestler started at must be in the correct date format', function () {
    $this->createRequest(StoreRequest::class)
        ->validate(WrestlerRequestFactory::new()->create([
            'started_at' => 'not-a-date',
        ]))
        ->assertFailsValidation(['started_at' => 'date']);
});
