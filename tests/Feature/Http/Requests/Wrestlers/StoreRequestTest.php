<?php

use App\Http\Requests\Wrestlers\StoreRequest;
use App\Models\Wrestler;
use App\Rules\LetterSpace;
use function Pest\Laravel\mock;
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

test('wrestler name must conain only letters and spaces', function () {
    mock(LetterSpace::class)
        ->shouldReceive('validate')
        ->with('name', 1, function ($closure) {
            $closure();
        });

    $this->createRequest(StoreRequest::class)
        ->validate(WrestlerRequestFactory::new()->create([
            'name' => 'HKJT*&^&*(^))',
        ]))
        ->assertFailsValidation(['name' => LetterSpace::class]);
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

test('wrestler height in feet cannot be more than eight', function () {
    $this->createRequest(StoreRequest::class)
        ->validate(WrestlerRequestFactory::new()->create([
            'feet' => 9,
        ]))
        ->assertFailsValidation(['feet' => 'max:8']);
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

test('wrestler weight must be only 3 digits', function () {
    $this->createRequest(StoreRequest::class)
        ->validate(WrestlerRequestFactory::new()->create([
            'weight' => 1000,
        ]))
        ->assertFailsValidation(['weight' => 'digits:3']);
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

test('wrestler signature move must be specific characters', function () {
    $this->createRequest(StoreRequest::class)
        ->validate(WrestlerRequestFactory::new()->create([
            'signature_move' => '09878&*%^&%^&()**',
        ]))
        ->assertFailsValidation(['signature_move' => 'regex:/^[a-zA-Z\s\']+$/']);
});

test('wrestler start date is optional', function () {
    $this->createRequest(StoreRequest::class)
        ->validate(WrestlerRequestFactory::new()->create([
            'start_date' => null,
        ]))
        ->assertPassesValidation();
});

test('wrestler start date must be a string if provided', function () {
    $this->createRequest(StoreRequest::class)
        ->validate(WrestlerRequestFactory::new()->create([
            'start_date' => 12345,
        ]))
        ->assertFailsValidation(['start_date' => 'string']);
});

test('wrestler start date must be in the correct date format', function () {
    $this->createRequest(StoreRequest::class)
        ->validate(WrestlerRequestFactory::new()->create([
            'start_date' => 'not-a-date',
        ]))
        ->assertFailsValidation(['start_date' => 'date']);
});
