<?php

declare(strict_types=1);

use App\Actions\Events\PersistEventPackAction;
use App\Data\Events\EventPackData;
use App\Models\Events\Event;
use App\Models\Events\EventPack;

test('persisting without a pack creates version one with the fingerprint and content', function (): void {
    // Arrange
    $event = Event::factory()->create();
    $content = ['timezone' => 'UTC', 'doors_at' => null, 'revision' => null, 'master' => null, 'notes' => 'First', 'follow_up' => null, 'matches' => [], 'segments' => []];

    // Act
    $pack = resolve(PersistEventPackAction::class)->handle($event, null, 0, 'card-a', $content);

    // Assert
    $stored = $event->pack()->sole();
    expect($pack->is($stored))->toBeTrue()
        ->and($stored->version)->toBe(1)
        ->and($stored->card_fingerprint)->toBe('card-a')
        ->and($stored->content)->toEqual($content);
});

test('persisting pack data merges it over the saved pack and keeps matches the data omits, sponsor tasks and production', function (): void {
    // Arrange
    $kept = ['match_id' => 7, 'planned_at' => null, 'revised_at' => null, 'minutes' => 10, 'notes' => 'Removed match note'];
    $replaced = ['match_id' => 8, 'planned_at' => null, 'revised_at' => null, 'minutes' => 5, 'notes' => 'Old'];
    $production = ['notes' => 'Plan', 'assignments' => [], 'cues' => []];
    $tasks = [['id' => 'a75f5717-2c9f-46df-b405-7f9670eb4995', 'label' => 'Banner', 'instruction' => 'Hang it', 'owner' => null]];
    $existing = EventPack::factory()->create(['version' => 1, 'content' => ['timezone' => 'UTC', 'doors_at' => null, 'revision' => null, 'master' => null, 'notes' => null, 'follow_up' => null,
        'matches' => [$kept, $replaced], 'segments' => [], 'sponsor_tasks' => $tasks, 'production' => $production]]);
    $data = EventPackData::fromValidated(['timezone' => 'UTC', 'notes' => 'New', 'matches' => [[...$replaced, 'notes' => 'New match note']], 'segments' => []]);

    // Act
    resolve(PersistEventPackAction::class)->handle($existing->event, $existing, 1, 'card-b', $data);

    // Assert
    $stored = EventPack::query()->sole();
    expect($stored->version)->toBe(2)
        ->and(array_keys($stored->content))->toBe(['timezone', 'doors_at', 'revision', 'master', 'notes', 'follow_up', 'matches', 'segments', 'sponsor_tasks', 'production'])
        ->and($stored->content['matches'])->toEqual([$kept, [...$replaced, 'notes' => 'New match note']])
        ->and(data_get($stored->content, 'sponsor_tasks'))->toEqual($tasks)
        ->and(data_get($stored->content, 'production'))->toEqual($production);
});

test('persisting over a pack writes the next version in place', function (): void {
    // Arrange
    $existing = EventPack::factory()->create(['version' => 4, 'card_fingerprint' => 'card-a']);
    $content = [...$existing->content, 'notes' => 'Replaced'];

    // Act
    $pack = resolve(PersistEventPackAction::class)->handle($existing->event, $existing, 4, 'card-b', $content);

    // Assert
    $stored = EventPack::query()->sole();
    expect($pack->is($existing))->toBeTrue()
        ->and($stored->version)->toBe(5)
        ->and($stored->card_fingerprint)->toBe('card-b')
        ->and($stored->content)->toEqual($content);
});
