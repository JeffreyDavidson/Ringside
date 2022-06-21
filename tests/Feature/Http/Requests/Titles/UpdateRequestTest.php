<?php

use App\Http\Requests\Titles\UpdateRequest;
use App\Models\Activation;
use App\Models\Title;
use Illuminate\Support\Carbon;
use Tests\Factories\TitleRequestFactory;

test('an administrator is authorized to make this request', function () {
    $this->createRequest(UpdateRequest::class)
        ->by(administrator())
        ->assertAuthorized();
});

test('a non administrator is not authorized to make this request', function () {
    $this->createRequest(UpdateRequest::class)
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
        ->assertFailsValidation(['name' => 'endswith:Title,Titles']);
});

test('title name must be unique', function () {
    $titleA = Title::factory()->create();
    Title::factory()->create(['name' => 'Example Name Title']);

    $this->createRequest(UpdateRequest::class)
        ->withParam('title', $titleA)
        ->validate(TitleRequestFactory::new()->create([
            'name' => 'Example Name Title',
        ]))
        ->assertFailsValidation(['name' => 'unique:titles,NULL,1,id']);
});

test('title activated at is optional', function () {
    $title = Title::factory()->create();

    $this->createRequest(UpdateRequest::class)
        ->withParam('title', $title)
        ->validate(TitleRequestFactory::new()->create([
            'activated_at' => null,
        ]))
        ->assertPassesValidation();
});

test('title activated at must be a string if provided', function () {
    $title = Title::factory()->create();

    $this->createRequest(UpdateRequest::class)
        ->withParam('title', $title)
        ->validate(TitleRequestFactory::new()->create([
            'activated_at' => 12345,
        ]))
        ->assertFailsValidation(['activated_at' => 'string']);
});

test('title activated at must be in the correct date format if provided', function () {
    $title = Title::factory()->create();

    $this->createRequest(UpdateRequest::class)
        ->withParam('title', $title)
        ->validate(TitleRequestFactory::new()->create([
            'activated_at' => 'not-a-date-format',
        ]))
        ->assertFailsValidation(['activated_at' => 'date']);
});

test('title activated at cannot be changed if activation start date has past', function () {
    $title = Title::factory()->active()->create();

    $this->createRequest(UpdateRequest::class)
        ->withParam('title', $title)
        ->validate(TitleRequestFactory::new()->create([
            'activated_at' => Carbon::now()->toDateTimeString(),
        ]))
        ->assertFailsValidation(['activated_at' => 'activation_date_cannot_be_changed']);
});

test('title activated at can be changed if activation start date is in the future', function () {
    $title = Title::factory()->has(Activation::factory()->started(Carbon::parse('+2 weeks')))->create();

    $this->createRequest(UpdateRequest::class)
        ->withParam('title', $title)
        ->validate(TitleRequestFactory::new()->create([
            'activated_at' => Carbon::tomorrow()->toDateString(),
        ]))
        ->assertPassesValidation();
});
