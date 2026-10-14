<?php

declare(strict_types=1);
use App\Actions\Tournaments\RenameTournamentAction;
use App\Actions\Tournaments\WithdrawTournamentEntryAction;
use App\Enums\Promotions\MembershipRole;
use App\Enums\Promotions\MembershipStatus;
use App\Enums\Tournaments\EntrantKind;
use App\Enums\Tournaments\TournamentStatus;
use App\Livewire\Tournaments\Components\Show;
use App\Models\Roster\Wrestlers\Wrestler;
use App\Services\Promotions\PromotionContextService;

use function Pest\Laravel\actingAs;
use function Pest\Livewire\livewire;

it('books from a reviewed node and updates the persisted bracket version', function (): void {
    $world = tournamentFixture();
    actingAs(administrator());
    $node = $world['tournament']->nodes()->firstOrFail();
    livewire(Show::class, ['tournamentId' => $world['tournament']->id])->call('begin', 'book', $node->id)->set('eventId', $world['event']->id)->set('refereeIds', [$world['referees']->sole()->id])->call('confirm')->assertHasNoErrors()->assertSee('Awaiting result');
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
    $page = livewire(Show::class, ['tournamentId' => $world['tournament']->id])->call('begin', 'unbook', $node->id)->set('reason', 'Rebuild this bout');
    $page->call('confirm')->assertHasErrors('confirmed');
    expect($match->refresh()->trashed())->toBeFalse();
    $page->set('confirmed', true)->call('confirm')->assertHasNoErrors();
    expect($match->refresh()->trashed())->toBeTrue();
});
it('preserves the draft inputs on failure and lets the operator refresh a stale screen', function (): void {
    $world = tournamentFixture();
    actingAs(administrator());
    $node = $world['tournament']->nodes()->firstOrFail();
    $page = livewire(Show::class, ['tournamentId' => $world['tournament']->id])->call('begin', 'book', $node->id)->set('eventId', $world['event']->id)->set('refereeIds', [$world['referees']->sole()->id]);
    resolve(RenameTournamentAction::class)->handle($world['tournament'], $world['tournament']->version, 'Revised Cup', null);
    $page->call('confirm')->assertHasErrors('operation')->assertSet('eventId', $world['event']->id)->call('refreshState')->assertHasNoErrors()->assertSee('Revised Cup');
    expect($world['event']->matches()->count())->toBe(0);
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
    livewire(Show::class, ['tournamentId' => $t->id])->call('begin', 'plan', $node->id)->set('eventId', $world['event']->id)->call('confirm')->assertHasNoErrors()->call('begin', 'plan', $node->id)->call('cancelDraft')->assertSet('draftOperation', '');
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
        resolve(WithdrawTournamentEntryAction::class)->handle($entry, $t->refresh()->version, 'Unavailable');
    }
    $page = livewire(Show::class, ['tournamentId' => $t->id])->call('begin', $operation, in_array($operation, ['replace', 'withdraw'], true) ? $entry->id : $node->id)->set('reason', 'Reviewed tournament change')->set('confirmed', true);
    if ($operation === 'replace') {
        $page->set('replacementId', $replacement->id);
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
