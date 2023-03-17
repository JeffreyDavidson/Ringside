<?php

use App\Http\Requests\Wrestlers\UpdateRequest;
use App\Models\Wrestler;
use App\Rules\EmploymentStartDateCanBeChanged;
use App\Rules\LetterSpace;
use Illuminate\Support\Carbon;
use function Pest\Laravel\mock;
use Tests\RequestFactories\WrestlerRequestFactory;

test('an administrator is authorized to make this request', function () {
    $wrestler = Wrestler::factory()->create();

    $this->createRequest(UpdateRequest::class)
        ->withParam('wrestler', $wrestler)
        ->by(administrator())
        ->assertAuthorized();
});

test('a non administrator is not authorized to make this request', function () {
    $wrestler = Wrestler::factory()->create();

    $this->createRequest(UpdateRequest::class)
        ->withParam('wrestler', $wrestler)
        ->by(basicUser())
        ->assertNotAuthorized();
});

test('wrestler name is required', function () {
    $wrestler = Wrestler::factory()->create();

    $this->createRequest(UpdateRequest::class)
        ->withParam('wrestler', $wrestler)
        ->validate(WrestlerRequestFactory::new()->create([
            'name' => null,
        ]))
        ->assertFailsValidation(['name' => 'required']);
});

test('wrestler name must be a string', function () {
    $wrestler = Wrestler::factory()->create();

    $this->createRequest(UpdateRequest::class)
        ->withParam('wrestler', $wrestler)
        ->validate(WrestlerRequestFactory::new()->create([
            'name' => 123,
        ]))
        ->assertFailsValidation(['name' => 'string']);
});

test('wrestler name must contain only letters and spaces', function () {
    $wrestler = Wrestler::factory()->create();

    mock(LetterSpace::class)
        ->shouldReceive('validate')
        ->with('name', 1, function ($closure) {
            $closure();
        });

    $this->createRequest(UpdateRequest::class)
        ->withParam('wrestler', $wrestler)
        ->validate(WrestlerRequestFactory::new()->create([
            'name' => 'dafd78(^&^',
        ]))
        ->assertFailsValidation(['name' => LetterSpace::class]);
});

test('wrestler name must be at least 3 characters', function () {
    $wrestler = Wrestler::factory()->create();

    $this->createRequest(UpdateRequest::class)
        ->withParam('wrestler', $wrestler)
        ->validate(WrestlerRequestFactory::new()->create([
            'name' => 'ab',
        ]))
        ->assertFailsValidation(['name' => 'min:3']);
});

test('wrestler name must be unique', function () {
    $wrestlerA = Wrestler::factory()->create(['name' => 'Example Wrestler Name A']);
    Wrestler::factory()->create(['name' => 'Example Wrestler Name B']);

    $this->createRequest(UpdateRequest::class)
        ->withParam('wrestler', $wrestlerA)
        ->validate(WrestlerRequestFactory::new()->create([
            'name' => 'Example Wrestler Name B',
        ]))
        ->assertFailsValidation(['name' => 'unique:wrestlers,NULL,'.$wrestlerA->id.',id']);
});

test('wrestler height in feet is required', function () {
    $wrestler = Wrestler::factory()->create();

    $this->createRequest(UpdateRequest::class)
        ->withParam('wrestler', $wrestler)
        ->validate(WrestlerRequestFactory::new()->create([
            'feet' => null,
        ]))
        ->assertFailsValidation(['feet' => 'required']);
});

test('wrestler height in feet must be an integer', function () {
    $wrestler = Wrestler::factory()->create();

    $this->createRequest(UpdateRequest::class)
        ->withParam('wrestler', $wrestler)
        ->validate(WrestlerRequestFactory::new()->create([
            'feet' => 'not-an-integer',
        ]))
        ->assertFailsValidation(['feet' => 'integer']);
});

test('wrestler height in feet must be lower than 8', function () {
    $wrestler = Wrestler::factory()->create();

    $this->createRequest(UpdateRequest::class)
        ->withParam('wrestler', $wrestler)
        ->validate(WrestlerRequestFactory::new()->create([
            'feet' => 9,
        ]))
        ->assertFailsValidation(['feet' => 'max:8']);
});

test('wrestler height in inches is required', function () {
    $wrestler = Wrestler::factory()->create();

    $this->createRequest(UpdateRequest::class)
        ->withParam('wrestler', $wrestler)
        ->validate(WrestlerRequestFactory::new()->create([
            'inches' => null,
        ]))
        ->assertFailsValidation(['inches' => 'required']);
});

test('wrestler height in inches must be an integer', function () {
    $wrestler = Wrestler::factory()->create();

    $this->createRequest(UpdateRequest::class)
        ->withParam('wrestler', $wrestler)
        ->validate(WrestlerRequestFactory::new()->create([
            'inches' => 'not-an-integer',
        ]))
        ->assertFailsValidation(['inches' => 'integer']);
});

test('wrestler height in inches has a max of 11', function () {
    $wrestler = Wrestler::factory()->create();

    $this->createRequest(UpdateRequest::class)
        ->withParam('wrestler', $wrestler)
        ->validate(WrestlerRequestFactory::new()->create([
            'inches' => 12,
        ]))
        ->assertFailsValidation(['inches' => 'max:11']);
});

test('wrestler weight is required', function () {
    $wrestler = Wrestler::factory()->create();

    $this->createRequest(UpdateRequest::class)
        ->withParam('wrestler', $wrestler)
        ->validate(WrestlerRequestFactory::new()->create([
            'weight' => null,
        ]))
        ->assertFailsValidation(['weight' => 'required']);
});

test('wrestler weight must be an integer', function () {
    $wrestler = Wrestler::factory()->create();

    $this->createRequest(UpdateRequest::class)
        ->withParam('wrestler', $wrestler)
        ->validate(WrestlerRequestFactory::new()->create([
            'weight' => 'not-an-integer',
        ]))
        ->assertFailsValidation(['weight' => 'integer']);
});

test('wrestler weight must 3 digits long', function () {
    $wrestler = Wrestler::factory()->create();

    $this->createRequest(UpdateRequest::class)
        ->withParam('wrestler', $wrestler)
        ->validate(WrestlerRequestFactory::new()->create([
            'weight' => 1000,
        ]))
        ->assertFailsValidation(['weight' => 'digits:3']);
});

test('wrestler hometown is required', function () {
    $wrestler = Wrestler::factory()->create();

    $this->createRequest(UpdateRequest::class)
        ->withParam('wrestler', $wrestler)
        ->validate(WrestlerRequestFactory::new()->create([
            'hometown' => null,
        ]))
        ->assertFailsValidation(['hometown' => 'required']);
});

test('wrestler hometown must be a string', function () {
    $wrestler = Wrestler::factory()->create();

    $this->createRequest(UpdateRequest::class)
        ->withParam('wrestler', $wrestler)
        ->validate(WrestlerRequestFactory::new()->create([
            'hometown' => 12345,
        ]))
        ->assertFailsValidation(['hometown' => 'string']);
});

test('wrestler hometown must only contain letters and spaces', function () {
    $wrestler = Wrestler::factory()->create();

    mock(LetterSpace::class)
        ->shouldReceive('validate')
        ->with('name', 1, function ($closure) {
            $closure();
        });

    $this->createRequest(UpdateRequest::class)
        ->withParam('wrestler', $wrestler)
        ->validate(WrestlerRequestFactory::new()->create([
            'hometown' => '@# 078^&*(^& ',
        ]))
        ->assertFailsValidation(['hometown' => LetterSpace::class]);
});

test('wrestler signature move is optional', function () {
    $wrestler = Wrestler::factory()->create();

    $this->createRequest(UpdateRequest::class)
        ->withParam('wrestler', $wrestler)
        ->validate(WrestlerRequestFactory::new()->create([
            'signature_move' => null,
        ]))
        ->assertPassesValidation();
});

test('wrestler signature move must be a string if provided', function () {
    $wrestler = Wrestler::factory()->create();

    $this->createRequest(UpdateRequest::class)
        ->withParam('wrestler', $wrestler)
        ->validate(WrestlerRequestFactory::new()->create([
            'signature_move' => 12345,
        ]))
        ->assertFailsValidation(['signature_move' => 'string']);
});

test('wrestler signature move must cannot contain special letters', function () {
    $wrestler = Wrestler::factory()->create();

    $this->createRequest(UpdateRequest::class)
        ->withParam('wrestler', $wrestler)
        ->validate(WrestlerRequestFactory::new()->create([
            'signature_move' => '*&%()&*&()*&&*',
        ]))
        ->assertFailsValidation(['signature_move' => 'regex:/^[a-zA-Z\s\']+$/']);
});

test('wrestler start date is optional', function () {
    $wrestler = Wrestler::factory()->create();

    $this->createRequest(UpdateRequest::class)
        ->withParam('wrestler', $wrestler)
        ->validate(WrestlerRequestFactory::new()->create([
            'start_date' => null,
        ]))
        ->assertPassesValidation();
});

test('wrestler start date must be a string if provided', function () {
    $wrestler = Wrestler::factory()->create();

    $this->createRequest(UpdateRequest::class)
        ->withParam('wrestler', $wrestler)
        ->validate(WrestlerRequestFactory::new()->create([
            'start_date' => 12345,
        ]))
        ->assertFailsValidation(['start_date' => 'string']);
});

test('wrestler start date must be in the correct date format if provided', function () {
    $wrestler = Wrestler::factory()->create();

    $this->createRequest(UpdateRequest::class)
        ->withParam('wrestler', $wrestler)
        ->validate(WrestlerRequestFactory::new()->create([
            'start_date' => 'not-a-date-format',
        ]))
        ->assertFailsValidation(['start_date' => 'date']);
});

test('wrestler start date cannot be changed if employment start date has past', function () {
    $wrestler = Wrestler::factory()->bookable()->create();

    mock(EmploymentStartDateCanBeChanged::class)
        ->shouldReceive('validate')
        ->with('start_date', 1, function ($closure) {
            $closure();
        });

    $this->createRequest(UpdateRequest::class)
        ->withParam('wrestler', $wrestler)
        ->validate(WrestlerRequestFactory::new()->create([
            'start_date' => Carbon::now()->toDateTimeString(),
        ]))
        ->assertFailsValidation(['start_date' => EmploymentStartDateCanBeChanged::class]);
});
