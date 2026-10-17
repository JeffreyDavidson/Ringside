<?php

declare(strict_types=1);
use App\Actions\Tournaments\SaveTournamentAction;
use App\Exceptions\RecordChangedException;
use App\Livewire\Tournaments\Components\Index;
use App\Models\Roster\Wrestlers\Wrestler;
use App\Models\Tournaments\Tournament;
use App\Services\Promotions\PromotionContextService;
use Illuminate\Support\Str;
use JMac\Testing\Double;
use Ramsey\Uuid\UuidInterface;

use function Pest\Laravel\actingAs;
use function Pest\Livewire\livewire;

it('creates a draft with manual seed order through the form', function (): void {
    $world = tournamentFixture();
    actingAs(administrator());
    $context = app(PromotionContextService::class);
    $context->set($world['promotion']);
    $context->enforce();
    $ids = $world['tournament']->entries()->get()->map(fn ($entry): int => $entry->competitor_id)->all();
    livewire(Index::class)->call('start')->set('form.name', 'Second Cup')->set('form.capacity', 4)->set('form.entrants', $ids)->call('move', 0, 1)->call('save')->assertHasNoErrors();
    $created = Tournament::query()->where('name', 'Second Cup')->firstOrFail();
    expect($created->entries()->firstOrFail()->competitor_id)->toBe($ids[1]);
});
it('requires a selected promotion before creating an owned tournament', function (): void {
    actingAs(administrator());
    livewire(Index::class)->call('start')->call('save')->assertHasErrors('operation');
    expect(Tournament::query()->count())->toBe(0);
});

it('keeps the create form and says so when the save finds a change', function (): void {
    // Arrange
    $world = tournamentFixture();
    actingAs(administrator());
    $context = app(PromotionContextService::class);
    $context->set($world['promotion']);
    $context->enforce();
    $save = Double::for(SaveTournamentAction::class);
    $save->expects('handle')
        ->throws(RecordChangedException::changed());
    app()->instance(SaveTournamentAction::class, $save);
    $ids = $world['tournament']->entries()->get()->map(fn ($entry): int => $entry->competitor_id)->all();
    $page = livewire(Index::class)
        ->call('start')
        ->set('form.name', 'Second Cup')
        ->set('form.capacity', 4)
        ->set('form.entrants', $ids);

    // Act
    $page->call('save');

    // Assert
    $page->assertHasErrors(['operation' => 'This was changed by someone else. Reload to see the latest version, review it, then try again. Your changes are still here.'])
        ->assertSet('form.name', 'Second Cup');
    expect(Tournament::query()->where('name', 'Second Cup')->exists())->toBeFalse();
    $save->verify();
});

it('filters create searches and clears entrants when switching kinds', function (): void {
    $world = tournamentFixture();
    actingAs(administrator());
    $context = app(PromotionContextService::class);
    $context->set($world['promotion']);
    $context->enforce();
    $page = livewire(Index::class)->call('start')->set('form.entrants', [$world['tournament']->entries()->firstOrFail()->competitor_id]);
    $page->call('searchRoster', 'referees', '')->assertReturned([])->call('searchRoster', 'wrestlers', '');
    $page->set('form.kind', 'tag_team')->assertSet('form.entrants', []);
});
it('retains invalid roster selections with a clear business error', function (): void {
    $world = tournamentFixture();
    actingAs(administrator());
    $context = app(PromotionContextService::class);
    $context->set($world['promotion']);
    $context->enforce();
    $ids = array_values($world['tournament']->entries()->get()->map(fn ($entry): int => $entry->competitor_id)->all());
    Wrestler::query()->findOrFail($ids[0])->delete();
    livewire(Index::class)->call('start')->set('form.name', 'Unavailable Cup')->set('form.capacity', 4)->set('form.entrants', $ids)->call('save')->assertHasErrors('operation')->assertSet('form.entrants', $ids);
    expect(Tournament::query()->where('name', 'Unavailable Cup')->exists())->toBeFalse();
});

it('refuses invalid tournament fields before anything is created', function (array $changes, string $error): void {
    // Arrange
    $world = tournamentFixture();
    actingAs(administrator());
    $context = app(PromotionContextService::class);
    $context->set($world['promotion']);
    $context->enforce();
    $ids = $world['tournament']->entries()->get()->map(fn ($entry): int => $entry->competitor_id)->all();
    $page = livewire(Index::class)
        ->call('start')
        ->set('form.name', 'Second Cup')
        ->set('form.capacity', 4)
        ->set('form.entrants', $ids);
    foreach ($changes as $field => $value) {
        $page->set($field, $value);
    }

    // Act
    $page->call('save');

    // Assert
    $page->assertHasErrors($error);
    expect(Tournament::query()->count())->toBe(1);
})->with([
    'blank name' => [['form.name' => ''], 'form.name'],
    'name over 255 characters' => [['form.name' => str_repeat('a', 256)], 'form.name'],
    'capacity outside 4, 8 and 16' => [['form.capacity' => 5], 'form.capacity'],
    'a single entrant' => [['form.entrants' => [1]], 'form.entrants'],
    'more entrants than capacity' => [['form.entrants' => [1, 2, 3, 4, 5]], 'form.entrants'],
    'a repeated entrant' => [['form.entrants' => [1, 1]], 'form.entrants.1'],
    'a missing entrant' => [['form.entrants' => [1, null]], 'form.entrants.1'],
]);

it('refuses a request id that is not a valid uuid', function (): void {
    // Arrange
    $world = tournamentFixture();
    actingAs(administrator());
    $context = app(PromotionContextService::class);
    $context->set($world['promotion']);
    $context->enforce();
    $ids = $world['tournament']->entries()->get()->map(fn ($entry): int => $entry->competitor_id)->all();
    $uuid = Double::for(UuidInterface::class);
    $uuid->allows('toString')
        ->returns('not-a-uuid');
    Str::createUuidsUsing(fn (): UuidInterface => $uuid);

    try {
        $page = livewire(Index::class)
            ->call('start')
            ->set('form.name', 'Second Cup')
            ->set('form.capacity', 4)
            ->set('form.entrants', $ids);

        // Act
        $page->call('save');
    } finally {
        Str::createUuidsNormally();
    }

    // Assert
    $page->assertHasErrors('form.requestId');
    expect(Tournament::query()->count())->toBe(1);
});
