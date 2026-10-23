<?php

declare(strict_types=1);
use App\Actions\Tournaments\RenameTournamentAction;
use App\Actions\Tournaments\WithdrawTournamentEntryAction;
use App\Enums\Promotions\MembershipRole;
use App\Enums\Promotions\MembershipStatus;
use App\Enums\Tournaments\EntrantKind;
use App\Enums\Tournaments\TournamentStatus;
use App\Exceptions\RecordChangedException;
use App\Lifecycle\Tournaments\TournamentLocks;
use App\Livewire\Tournaments\Components\Show;
use App\Models\Roster\Wrestlers\Wrestler;
use App\Services\Promotions\PromotionContextService;
use JMac\Testing\Double;
use Livewire\Features\SupportLockedProperties\CannotUpdateLockedPropertyException;

use function Pest\Laravel\actingAs;
use function Pest\Livewire\livewire;

it('books from a reviewed node and updates the persisted bracket version', function (): void {
    $world = tournamentFixture();
    actingAs(administrator());
    $node = $world['tournament']->nodes()->firstOrFail();
    livewire(Show::class, ['tournamentId' => $world['tournament']->id])->call('begin', 'book', $node->id)->set('draft.eventId', $world['event']->id)->set('draft.refereeIds', [$world['referees']->sole()->id])->call('confirm')->assertHasNoErrors()->assertSee('Awaiting result');
    expect($node->links()->count())->toBe(1)->and($world['tournament']->refresh()->version)->toBe(2);
});
it('blocks a view-only member from invoking a protected operation', function (): void {
    $world = tournamentFixture();
    $user = basicUser();
    $world['promotion']->users()->attach($user, ['role' => MembershipRole::Member, 'status' => MembershipStatus::Active]);
    actingAs($user);
    $context = app(PromotionContextService::class);
    $context->set($world['promotion']);
    $context->enforce();
    livewire(Show::class, ['tournamentId' => $world['tournament']->id])->call('begin', 'book', $world['tournament']->nodes()->firstOrFail()->id)->assertForbidden();
    expect($world['event']->matches()->count())->toBe(0);
});
it('requires review confirmation before removing an unplayed booking', function (): void {
    $world = tournamentFixture();
    actingAs(administrator());
    $node = $world['tournament']->nodes()->firstOrFail();
    $match = bookTournamentNode($world, $node);
    $page = livewire(Show::class, ['tournamentId' => $world['tournament']->id])->call('begin', 'unbook', $node->id)->set('draft.reason', 'Rebuild this bout');
    $page->call('confirm')->assertHasErrors('draft.confirmed');
    expect($match->refresh()->trashed())->toBeFalse();
    $page->set('draft.confirmed', true)->call('confirm')->assertHasNoErrors();
    expect($match->refresh()->trashed())->toBeTrue();
});
it('preserves the draft inputs on failure and lets the operator refresh a stale screen', function (): void {
    $world = tournamentFixture();
    actingAs(administrator());
    $node = $world['tournament']->nodes()->firstOrFail();
    $page = livewire(Show::class, ['tournamentId' => $world['tournament']->id])->call('begin', 'book', $node->id)->set('draft.eventId', $world['event']->id)->set('draft.refereeIds', [$world['referees']->sole()->id]);
    resolve(RenameTournamentAction::class)->handle($world['tournament'], $world['tournament']->version, 'Revised Cup', null, signedInUser());
    $page->call('confirm')->assertHasErrors(['operation' => 'This was changed by someone else. Reload to see the latest version, review it, then try again. Your changes are still here.'])->assertSet('draft.eventId', $world['event']->id)->call('refreshState')->assertHasNoErrors()->assertSee('Revised Cup');
    expect($world['event']->matches()->count())->toBe(0);
});

it('keeps the draft and says so when the tournament lock finds a change', function (): void {
    // Arrange
    $world = tournamentFixture();
    actingAs(administrator());
    $page = livewire(Show::class, ['tournamentId' => $world['tournament']->id])
        ->call('begin', 'cancel')
        ->set('draft.reason', 'Venue closed')
        ->set('draft.confirmed', true);
    $locks = Double::for(TournamentLocks::class);
    $locks->expects('lock')
        ->throws(RecordChangedException::changed());
    app()->instance(TournamentLocks::class, $locks);

    // Act
    $page->call('confirm');

    // Assert
    $page->assertHasErrors(['operation' => 'This was changed by someone else. Reload to see the latest version, review it, then try again. Your changes are still here.'])
        ->assertSet('draft.reason', 'Venue closed');
    expect($world['tournament']->refresh()->status)->not->toBe(TournamentStatus::Cancelled);
    $locks->verify();
});

it('edits a draft seed order and saves its reviewed configuration', function (): void {
    $world = tournamentFixture();
    actingAs(administrator());
    $t = $world['tournament'];
    $ids = array_values($t->entries()->get()->map(fn ($entry): int => $entry->competitor_id)->all());
    livewire(Show::class, ['tournamentId' => $t->id])->call('move', 0, 1)->call('move', -1, 1)->set('form.name', 'Winter Cup')->call('save')->assertHasNoErrors()->assertSee('Winter Cup');
    expect($t->refresh()->name)->toBe('Winter Cup')->and($t->entries()->firstOrFail()->competitor_id)->toBe($ids[1]);
});
it('renames an active tournament and clears entries when changing a draft kind', function (): void {
    $world = tournamentFixture();
    actingAs(administrator());
    $t = $world['tournament'];
    bookTournamentNode($world, $t->nodes()->firstOrFail());
    livewire(Show::class, ['tournamentId' => $t->id])->set('form.name', 'Winter Cup')->call('rename')->assertHasNoErrors()->set('form.kind', 'tag_team')->assertSet('form.entrants', [])->call('view', 'bracket')->assertSee('Scroll within the bracket')->call('view', 'rounds')->assertDontSee('Scroll within the bracket');
    expect($t->refresh()->name)->toBe('Winter Cup');
});
it('plans an unresolved final and closes an abandoned review', function (): void {
    $world = tournamentFixture();
    actingAs(administrator());
    $t = $world['tournament'];
    $node = $t->nodes()->where('round', 2)->firstOrFail();
    livewire(Show::class, ['tournamentId' => $t->id])->call('begin', 'plan', $node->id)->set('draft.eventId', $world['event']->id)->call('confirm')->assertHasNoErrors()->call('begin', 'plan', $node->id)->call('cancelDraft')->assertSet('draft.operation', '');
    expect($node->refresh()->planned_event_id)->toBe($world['event']->id)->and($world['event']->matches()->count())->toBe(0);
});
it('performs reviewed entry administration through the protected screen', function (string $operation): void {
    $world = tournamentFixture();
    actingAs(administrator());
    $t = $world['tournament'];
    bookTournamentNode($world, $t->nodes()->where('round', 1)->where('position', 2)->firstOrFail());
    $node = $t->nodes()->where('round', 1)->where('position', 1)->firstOrFail();
    $entry = $t->entries()->firstOrFail();
    $replacement = Wrestler::factory()->employed()->for($world['promotion'])->create();

    if ($operation === 'walkover') {
        resolve(WithdrawTournamentEntryAction::class)->handle($entry, $t->refresh()->version, 'Unavailable', signedInUser());
    }

    $page = livewire(Show::class, ['tournamentId' => $t->id])->call('begin', $operation, in_array($operation, ['replace', 'withdraw'], true) ? $entry->id : $node->id)->set('draft.reason', 'Reviewed tournament change')->set('draft.confirmed', true);

    if ($operation === 'replace') {
        $page->set('draft.replacementId', $replacement->id);
    }

    $page->call('confirm')->assertHasNoErrors();
    match ($operation) {
        default => throw new InvalidArgumentException('Unknown operation'),
        'withdraw' => expect($entry->refresh()->withdrawn)->toBeTrue(),
        'replace' => expect($entry->refresh()->competitor_id)->toBe($replacement->id),
        'walkover' => expect($node->refresh()->walkover_entry_id)->not->toBeNull(),
        'reset' => expect($t->refresh()->version)->toBe(2),
        'cancel' => expect($t->refresh()->status)->toBe(TournamentStatus::Cancelled),
    };
})->with(['withdraw', 'replace', 'walkover', 'reset', 'cancel']);
it('searches only the authorized roster and refreshes after an ordinary result event', function (): void {
    $world = tournamentFixture();
    actingAs(administrator());
    $t = $world['tournament'];
    $node = $t->nodes()->firstOrFail();
    $match = bookTournamentNode($world, $node);
    $page = livewire(Show::class, ['tournamentId' => $t->id]);
    $page->call('searchRoster', 'invalid', '')->assertReturned([]);
    $page->call('searchRoster', 'wrestlers', '');
    recordTournamentWinner($match, $t);
    $page->dispatch('refreshDatatable')->assertSet('version', $t->refresh()->version)->assertSee('Pinfall');
});

it('labels current unavailability without erasing historical qualifiers', function (EntrantKind $kind): void {
    $world = tournamentFixture(kind: $kind);
    $t = $world['tournament'];
    actingAs(administrator());
    $entry = $t->entries()->firstOrFail();
    $entry->competitor()->firstOrFail()->delete();
    livewire(Show::class, ['tournamentId' => $t->id])->assertSee('No longer bookable');
    expect($t->links()->count())->toBe(0)->and($t->entries()->count())->toBe(4);
})->with(EntrantKind::cases());

it('refuses an invalid name or description when renaming and changes nothing', function (string $field, string $value, string $error): void {
    // Arrange
    $world = tournamentFixture();
    actingAs(administrator());
    $tournament = $world['tournament'];
    $name = $tournament->name;

    // Act
    $page = livewire(Show::class, ['tournamentId' => $tournament->id])
        ->set($field, $value)
        ->call('rename');

    // Assert
    $page->assertHasErrors([$field => $error]);
    expect($tournament->refresh()->name)->toBe($name)
        ->and($tournament->version)->toBe(1);
})->with([
    'blank name' => ['form.name', '', 'required'],
    'name over 255 characters' => ['form.name', str_repeat('a', 256), 'max'],
    'description over 10000 characters' => ['form.description', str_repeat('a', 10001), 'max'],
]);

it('refuses a reason that is missing, too long or unconfirmed before any tournament operation runs', function (string $operation, string $reason, bool $confirmed, string $field): void {
    // Arrange
    $world = tournamentFixture();
    actingAs(administrator());
    $tournament = $world['tournament'];
    $target = in_array($operation, ['replace', 'withdraw'], true) ? $tournament->entries()->firstOrFail()->id : $tournament->nodes()->firstOrFail()->id;
    $page = livewire(Show::class, ['tournamentId' => $tournament->id])
        ->call('begin', $operation, $target)
        ->set('draft.reason', $reason)
        ->set('draft.confirmed', $confirmed)
        ->set('draft.replacementId', Wrestler::factory()->employed()->for($world['promotion'])->create()->id);

    // Act
    $page->call('confirm');

    // Assert
    $page->assertHasErrors($field);
    expect($tournament->refresh()->version)->toBe(1)
        ->and($tournament->status)->not->toBe(TournamentStatus::Cancelled);
})->with(function (): Generator {
    foreach (['reset', 'unbook', 'withdraw', 'replace', 'walkover', 'cancel'] as $operation) {
        yield "{$operation} without a reason" => [$operation, '', true, 'draft.reason'];
        yield "{$operation} with a reason over 2000 characters" => [$operation, str_repeat('a', 2001), true, 'draft.reason'];
        yield "{$operation} unconfirmed" => [$operation, 'Because.', false, 'draft.confirmed'];
    }
});

it('refuses invalid tournament fields when saving an existing tournament', function (array $changes, string $error): void {
    // Arrange
    $world = tournamentFixture();
    actingAs(administrator());
    $tournament = $world['tournament'];
    $name = $tournament->name;
    $page = livewire(Show::class, ['tournamentId' => $tournament->id]);

    foreach ($changes as $field => $value) {
        $page->set($field, $value);
    }

    // Act
    $page->call('save');

    // Assert
    $page->assertHasErrors($error);
    expect($tournament->refresh()->name)->toBe($name)
        ->and($tournament->version)->toBe(1);
})->with([
    'blank name' => [['form.name' => ''], 'form.name'],
    'capacity outside 4, 8 and 16' => [['form.capacity' => 5], 'form.capacity'],
    'a single entrant' => [['form.entrants' => [1]], 'form.entrants'],
    'more entrants than capacity' => [['form.capacity' => 4, 'form.entrants' => [1, 2, 3, 4, 5]], 'form.entrants'],
    'a repeated entrant' => [['form.entrants' => [1, 1]], 'form.entrants.1'],
    'a missing entrant' => [['form.entrants' => [1, null]], 'form.entrants.1'],
]);

it('refuses to draft an operation that does not exist and opens no draft', function (): void {
    // Arrange
    $world = tournamentFixture();
    actingAs(administrator());
    $tournament = $world['tournament'];
    $node = $tournament->nodes()->firstOrFail();
    $page = livewire(Show::class, ['tournamentId' => $tournament->id]);

    // Act
    $page->call('begin', 'teleport', $node->id);

    // Assert
    $page->assertHasErrors('operation')
        ->assertSet('draft.operation', '')
        ->assertSet('nodeId', null);
    expect($tournament->refresh()->version)->toBe(1);
});

it('refuses a client change to a locked draft property and keeps the draft unchanged', function (string $property, string $value): void {
    // Arrange
    $world = tournamentFixture();
    $other = tournamentFixture();
    actingAs(administrator());
    $node = $world['tournament']->nodes()->firstOrFail();
    $page = livewire(Show::class, ['tournamentId' => $world['tournament']->id])
        ->call('begin', 'book', $node->id);
    $original = $page->get("draft.{$property}");
    $newValue = $property === 'promotionId' ? $other['promotion']->id : $value;

    // Act
    $change = fn () => $page->set("draft.{$property}", $newValue);

    // Assert
    expect($change)->toThrow(CannotUpdateLockedPropertyException::class);
    $page->assertSet("draft.{$property}", $original);
})->with([
    'promotion id' => ['promotionId', ''],
    'entrant kind' => ['entrantKind', 'tag_team'],
    'operation' => ['operation', 'cancel'],
]);
