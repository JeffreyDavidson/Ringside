<?php

use App\Http\Requests\Referees\UpdateRequest;
use App\Models\Referee;
use Illuminate\Support\Carbon;
use Tests\RequestFactories\RefereeRequestFactory;

test('an administrator is authorized to make this request', function () {
    $referee = Referee::factory()->create();

    $this->createRequest(UpdateRequest::class)
        ->withParam('referee', $referee)
        ->by(administrator())
        ->assertAuthorized();
});

test('a non administrator is not authorized to make this request', function () {
    $referee = Referee::factory()->create();

    $this->createRequest(UpdateRequest::class)
        ->withParam('referee', $referee)
        ->by(basicUser())
        ->assertNotAuthorized();
});

test('referee first name is required', function () {
    $referee = Referee::factory()->create();

    $this->createRequest(UpdateRequest::class)
        ->withParam('referee', $referee)
        ->validate(RefereeRequestFactory::new()->create([
            'first_name' => null,
        ]))
        ->assertFailsValidation(['first_name' => 'required']);
});

test('referee first name must be a string', function () {
    $referee = Referee::factory()->create();

    $this->createRequest(UpdateRequest::class)
        ->withParam('referee', $referee)
        ->validate(RefereeRequestFactory::new()->create([
            'first_name' => 12345,
        ]))
        ->assertFailsValidation(['first_name' => 'string']);
});

test('referee first name must be at least 3 characters', function () {
    $referee = Referee::factory()->create();

    $this->createRequest(UpdateRequest::class)
        ->withParam('referee', $referee)
        ->validate(RefereeRequestFactory::new()->create([
            'first_name' => 'ab',
        ]))
        ->assertFailsValidation(['first_name' => 'min:3']);
});

test('referee last name is required', function () {
    $referee = Referee::factory()->create();

    $this->createRequest(UpdateRequest::class)
        ->withParam('referee', $referee)
        ->validate(RefereeRequestFactory::new()->create([
            'last_name' => null,
        ]))
        ->assertFailsValidation(['last_name' => 'required']);
});

test('referee last name must be a string', function () {
    $referee = Referee::factory()->create();

    $this->createRequest(UpdateRequest::class)
        ->withParam('referee', $referee)
        ->validate(RefereeRequestFactory::new()->create([
            'last_name' => 12345,
        ]))
        ->assertFailsValidation(['last_name' => 'string']);
});

test('referee last name must be at least 3 characters', function () {
    $referee = Referee::factory()->create();

    $this->createRequest(UpdateRequest::class)
        ->withParam('referee', $referee)
        ->validate(RefereeRequestFactory::new()->create([
            'last_name' => 'ab',
        ]))
        ->assertFailsValidation(['last_name' => 'min:3']);
});

test('referee start date is optional', function () {
    $referee = Referee::factory()->create();

    $this->createRequest(UpdateRequest::class)
        ->withParam('referee', $referee)
        ->validate(RefereeRequestFactory::new()->create([
            'start_date' => null,
        ]))
        ->assertPassesValidation();
});

test('referee start date must be a string if provided', function () {
    $referee = Referee::factory()->create();

    $this->createRequest(UpdateRequest::class)
        ->withParam('referee', $referee)
        ->validate(RefereeRequestFactory::new()->create([
            'start_date' => 12345,
        ]))
        ->assertFailsValidation(['start_date' => 'string']);
});

test('referee start date must be in the correct date format', function () {
    $referee = Referee::factory()->create();

    $this->createRequest(UpdateRequest::class)
        ->withParam('referee', $referee)
        ->validate(RefereeRequestFactory::new()->create([
            'start_date' => 'not-a-date-format',
        ]))
        ->assertFailsValidation(['start_date' => 'date']);
});

test('referee start date cannot be changed if employment start date has past', function () {
    $referee = Referee::factory()->bookable()->create();

    $this->createRequest(UpdateRequest::class)
        ->withParam('referee', $referee)
        ->validate(RefereeRequestFactory::new()->create([
            'start_date' => Carbon::now()->toDateTimeString(),
        ]))
        ->assertFailsValidation(['start_date' => 'app\rules\employmentstartdatecanbechanged']);
});

test('referee start date can be changed if employment start date is in the future', function () {
    $referee = Referee::factory()->withFutureEmployment()->create();

    $this->createRequest(UpdateRequest::class)
        ->withParam('referee', $referee)
        ->validate(RefereeRequestFactory::new()->create([
            'start_date' => Carbon::tomorrow()->toDateString(),
        ]))
        ->assertPassesValidation();
});
