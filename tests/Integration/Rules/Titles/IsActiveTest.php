<?php

declare(strict_types=1);

use App\Models\Events\Event;
use App\Models\Titles\Title;
use App\Rules\Titles\IsActive;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Validator;

function titleIsRejected(Title $title, ?Event $event): bool
{
    return Validator::make(
        ['titles' => [$title->id]],
        ['titles.*' => [new IsActive($event)]],
    )->errors()->has('titles.0');
}

test('without an event it accepts a title that is active now', function () {
    // Arrange
    $title = Title::factory()
        ->active()
        ->create();

    // Act
    $rejected = titleIsRejected($title, null);

    // Assert
    expect($rejected)->toBeFalse();
});

test('for an unscheduled event it rejects a title that is not active now', function () {
    // Arrange
    $title = Title::factory()
        ->withActivationPeriod(now()->subDays(30), now()->subDays(10))
        ->create();
    $event = Event::factory()
        ->unscheduled()
        ->create();

    // Act
    $rejected = titleIsRejected($title, $event);

    // Assert
    expect($rejected)->toBeTrue();
});

test('for an unscheduled event it rejects a title whose activity starts in the future', function () {
    // Arrange
    $title = Title::factory()
        ->withFutureActivation()
        ->create();
    $event = Event::factory()
        ->unscheduled()
        ->create();

    // Act
    $rejected = titleIsRejected($title, $event);

    // Assert
    expect($rejected)->toBeTrue();
});

test('it accepts a title that was active on the show date but is inactive now', function () {
    // Arrange
    $title = Title::factory()
        ->withActivationPeriod(Carbon::parse('2026-01-01 00:00:00'), Carbon::parse('2026-06-01 00:00:00'))
        ->create();
    $event = Event::factory()->create(['date' => '2026-03-10 19:00:00']);

    // Act
    $rejected = titleIsRejected($title, $event);

    // Assert
    expect($rejected)->toBeFalse();
});

test('it rejects a title that had not debuted on the show date', function () {
    // Arrange
    $title = Title::factory()
        ->withActivationPeriod(Carbon::parse('2026-05-01 00:00:00'))
        ->create();
    $event = Event::factory()->create(['date' => '2026-03-10 19:00:00']);

    // Act
    $rejected = titleIsRejected($title, $event);

    // Assert
    expect($rejected)->toBeTrue();
});

test('it rejects a title that was inactive on the show date', function () {
    // Arrange
    $title = Title::factory()
        ->withActivationPeriod(Carbon::parse('2026-01-01 00:00:00'), Carbon::parse('2026-02-01 00:00:00'))
        ->create();
    $event = Event::factory()->create(['date' => '2026-03-10 19:00:00']);

    // Act
    $rejected = titleIsRejected($title, $event);

    // Assert
    expect($rejected)->toBeTrue();
});

test('it rejects a title that does not exist', function () {
    // Arrange
    $validator = Validator::make(
        ['titles' => [999999]],
        ['titles.*' => [new IsActive]],
    );

    // Act
    $rejected = $validator->errors()->has('titles.0');

    // Assert
    expect($rejected)->toBeTrue();
});
