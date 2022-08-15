<?php

use App\Http\Requests\Titles\StoreRequest;
use App\Models\Title;
use Tests\RequestFactories\TitleRequestFactory;

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

test('title name is required', function () {
    $this->createRequest(StoreRequest::class)
        ->validate(TitleRequestFactory::new()->create([
            'name' => null,
        ]))
        ->assertFailsValidation(['name' => 'required']);
});

test('title name must be a string', function () {
    $this->createRequest(StoreRequest::class)
        ->validate(TitleRequestFactory::new()->create([
            'name' => 123,
        ]))
        ->assertFailsValidation(['name' => 'string']);
});

test('title name must be at least 3 characters', function () {
    $this->createRequest(StoreRequest::class)
        ->validate(TitleRequestFactory::new()->create([
            'name' => 'ab',
        ]))
        ->assertFailsValidation(['name' => 'min:3']);
});

test('title name must be unique', function () {
    Title::factory()->create(['name' => 'Example Name Title']);

    $this->createRequest(StoreRequest::class)
        ->validate(TitleRequestFactory::new()->create([
            'name' => 'Example Name Title',
        ]))
        ->assertFailsValidation(['name' => 'unique:titles,name,NULL,id']);
});

test('title name must end with title or titles', function () {
    $this->createRequest(StoreRequest::class)
        ->validate(TitleRequestFactory::new()->create([
            'name' => 'Example Name',
        ]))
        ->assertFailsValidation(['name' => 'endswith:Title,Titles']);
});

test('title activation date is optional', function () {
    $this->createRequest(StoreRequest::class)
        ->validate(TitleRequestFactory::new()->create([
            'activation_date' => null,
        ]))
        ->assertPassesValidation();
});

test('title activation date must be a string if provided', function () {
    $this->createRequest(StoreRequest::class)
        ->validate(TitleRequestFactory::new()->create([
            'activation_date' => 12345,
        ]))
        ->assertFailsValidation(['activation_date' => 'string']);
});

test('title activation date must be a in the correct date format', function () {
    $this->createRequest(StoreRequest::class)
        ->validate(TitleRequestFactory::new()->create([
            'activation_date' => 'not-a-date',
        ]))
        ->assertFailsValidation(['activation_date' => 'date']);
});
