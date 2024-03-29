<?php

declare(strict_types=1);

use App\Http\Requests\Titles\UpdateRequest;
use App\Models\Title;
use App\Rules\ActivationStartDateCanBeChanged;
use Illuminate\Support\Carbon;
use Tests\RequestFactories\TitleRequestFactory;

test('an administrator is authorized to make this request', function () {
    $title = Title::factory()->create();

    $this->createRequest(UpdateRequest::class)
        ->withParam('title', $title)
        ->by(administrator())
        ->assertAuthorized();
});

test('a non administrator is not authorized to make this request', function () {
    $title = Title::factory()->create();

    $this->createRequest(UpdateRequest::class)
        ->withParam('title', $title)
        ->by(basicUser())
        ->assertNotAuthorized();
});

test('title name is required', function () {
    $title = Title::factory()->create();

    $this->createRequest(UpdateRequest::class)
        ->withParam('title', $title)
        ->validate(TitleRequestFactory::new()->create([
            'name' => null,
        ]))
        ->assertFailsValidation(['name' => 'required']);
});

test('title name must be a string', function () {
    $title = Title::factory()->create();

    $this->createRequest(UpdateRequest::class)
        ->withParam('title', $title)
        ->validate(TitleRequestFactory::new()->create([
            'name' => 123,
        ]))
        ->assertFailsValidation(['name' => 'string']);
});

test('title name must be at least 3 characters', function () {
    $title = Title::factory()->create();

    $this->createRequest(UpdateRequest::class)
        ->withParam('title', $title)
        ->validate(TitleRequestFactory::new()->create([
            'name' => 'ab',
        ]))
        ->assertFailsValidation(['name' => 'min:3']);
});

test('title name must end with title or titles', function () {
    $title = Title::factory()->create();

    $this->createRequest(UpdateRequest::class)
        ->withParam('title', $title)
        ->validate(TitleRequestFactory::new()->create([
            'name' => 'Example Name',
        ]))
        ->assertFailsValidation(['name' => 'ends_with:Title,Titles']);
});

test('title name must be unique', function () {
    $titleA = Title::factory()->create();
    Title::factory()->create(['name' => 'Example Name Title']);

    $this->createRequest(UpdateRequest::class)
        ->withParam('title', $titleA)
        ->validate(TitleRequestFactory::new()->create([
            'name' => 'Example Name Title',
        ]))
        ->assertFailsValidation(['name' => 'unique:titles,NULL,'.$titleA->id.',id']);
});

test('title activation date is optional', function () {
    $title = Title::factory()->create();

    $this->createRequest(UpdateRequest::class)
        ->withParam('title', $title)
        ->validate(TitleRequestFactory::new()->create([
            'activation_date' => null,
        ]))
        ->assertPassesValidation();
});

test('title activation date must be a string if provided', function () {
    $title = Title::factory()->create();

    $this->createRequest(UpdateRequest::class)
        ->withParam('title', $title)
        ->validate(TitleRequestFactory::new()->create([
            'activation_date' => 12345,
        ]))
        ->assertFailsValidation(['activation_date' => 'string']);
});

test('title activation date must be in the correct date format if provided', function () {
    $title = Title::factory()->create();

    $this->createRequest(UpdateRequest::class)
        ->withParam('title', $title)
        ->validate(TitleRequestFactory::new()->create([
            'activation_date' => 'not-a-date-format',
        ]))
        ->assertFailsValidation(['activation_date' => 'date']);
});

test('title activation date cannot be changed if activation start date has past', function () {
    $title = Title::factory()->active()->create();

    Mockery::mock(ActivationStartDateCanBeChanged::class)
        ->shouldReceive('validate')
        ->with('activation_date', 1, function ($closure) {
            $closure();
        });

    $this->createRequest(UpdateRequest::class)
        ->withParam('title', $title)
        ->validate(TitleRequestFactory::new()->create([
            'activation_date' => Carbon::now()->toDateTimeString(),
        ]))
        ->assertFailsValidation(['activation_date' => ActivationStartDateCanBeChanged::class]);
});
