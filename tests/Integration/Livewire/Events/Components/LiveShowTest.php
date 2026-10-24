<?php

declare(strict_types=1);

require_once __DIR__.'/../../../../Fixtures/live-show.php';

use App\Actions\Events\CompleteLiveShowAction;
use App\Actions\Events\PrepareLiveShowAction;
use App\Actions\Events\SaveEventPackAction;
use App\Data\Events\EventPackData;
use App\Enums\Events\LiveShowStatus;
use App\Enums\Promotions\MembershipRole;
use App\Enums\Promotions\MembershipStatus;
use App\Livewire\Events\Components\LiveShow;
use App\Models\Events\Event;
use App\Models\Events\EventLiveRun;
use App\Models\Events\Venue;
use App\Models\Matches\EventMatch;
use App\Models\Promotions\Promotion;
use App\Models\Roster\Wrestlers\Wrestler;
use App\Services\Promotions\PromotionContextService;
use App\ViewModels\Events\EventPackViewModel;
use JMac\Testing\Double;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;
use function Pest\Livewire\livewire;

test('promotion members can view but only managers and owners operate the live show', function (MembershipRole $role, bool $allowed): void {
    // Arrange
    $promotion = Promotion::factory()->create();
    $event = Event::factory()->for($promotion)->create(['date' => now()]);
    EventMatch::factory()->for($event)->create();
    actingAs(administrator());
    $data = resolve(EventPackViewModel::class)->data($event);
    resolve(SaveEventPackAction::class)->handle($event, 0, $data['card']['fingerprint'], EventPackData::fromValidated($data['content']), false);
    $user = basicUser();
    $promotion->users()->attach($user, ['role' => $role, 'status' => MembershipStatus::Active]);
    actingAs($user);
    get(route('events.live-show.show', $event))->assertOk();

    // Act
    $component = livewire(LiveShow::class, ['eventId' => $event->id]);
    $component->call('prepare');

    // Assert
    if (! $allowed) {
        $component->assertForbidden();
        expect(EventLiveRun::query()->count())->toBe(0);

        return;
    }

    $component->assertHasNoErrors()->assertSee('Roster checklist');
    expect(EventLiveRun::query()->where('event_id', $event->id)->count())->toBe(1);
})->with([
    'member' => [MembershipRole::Member, false],
    'manager' => [MembershipRole::Manager, true],
    'owner' => [MembershipRole::Owner, true],
]);

test('a stale screen is refreshed without overwriting the first operator start', function (): void {
    // Arrange
    actingAs(administrator());
    $event = Event::factory()->create(['date' => now()]);
    EventMatch::factory()->for($event)->create();
    $data = resolve(EventPackViewModel::class)->data($event);
    resolve(SaveEventPackAction::class)->handle($event, 0, $data['card']['fingerprint'], EventPackData::fromValidated($data['content']), false);
    $run = resolve(PrepareLiveShowAction::class)->handle($event, 0, 1, $data['card']['fingerprint'], signedInUser());
    $first = livewire(LiveShow::class, ['eventId' => $event->id]);
    $stale = livewire(LiveShow::class, ['eventId' => $event->id]);
    $key = $run->content['items'][0]['key'];

    // Act
    $first->call('item', $key, 'start');
    $stale->call('item', $key, 'start');

    // Assert
    $stale->assertHasErrors('run')->assertSet('version', 2);
    expect($run->refresh()->version)->toBe(2)
        ->and($run->content['items'][0]['start'])->not->toBeNull();
});

test('polling does not replace an open correction draft or its original version', function (): void {
    // Arrange
    actingAs(administrator());
    $event = Event::factory()->create(['date' => now()]);
    EventMatch::factory()->for($event)->create();
    $data = resolve(EventPackViewModel::class)->data($event);
    resolve(SaveEventPackAction::class)->handle($event, 0, $data['card']['fingerprint'], EventPackData::fromValidated($data['content']), false);
    $run = resolve(PrepareLiveShowAction::class)->handle($event, 0, 1, $data['card']['fingerprint'], signedInUser());
    $key = $run->content['items'][0]['key'];
    $component = livewire(LiveShow::class, ['eventId' => $event->id]);
    $component->call('beginCorrection', $key, 'skip');
    $component->set('reason', 'Keep my draft');
    $operator = livewire(LiveShow::class, ['eventId' => $event->id]);
    $operator->call('item', $key, 'start');

    // Act
    $component->call('refreshState');
    $component->call('saveCorrection');

    // Assert
    $component->assertSet('reason', 'Keep my draft')->assertSet('draftVersion', 1)->assertHasErrors('run');
    expect($run->refresh()->content['items'][0]['state'])->toBe('running');
});

test('membership suspension after mount refuses all further live changes', function (): void {
    // Arrange
    $promotion = Promotion::factory()->create();
    $event = Event::factory()->for($promotion)->create(['date' => now()]);
    EventMatch::factory()->for($event)->create();
    actingAs(administrator());
    $data = resolve(EventPackViewModel::class)->data($event);
    resolve(SaveEventPackAction::class)->handle($event, 0, $data['card']['fingerprint'], EventPackData::fromValidated($data['content']), false);
    $user = basicUser();
    $promotion->users()->attach($user, ['role' => MembershipRole::Manager, 'status' => MembershipStatus::Active]);
    actingAs($user);
    get(route('events.live-show.show', $event))->assertOk();
    $component = livewire(LiveShow::class, ['eventId' => $event->id]);
    $promotion->users()->updateExistingPivot($user->id, ['status' => MembershipStatus::Suspended]);
    resolve(PromotionContextService::class)->forgetMemberships();

    // Act
    $component->call('prepare');

    // Assert
    $component->assertForbidden();
    expect(EventLiveRun::query()->count())->toBe(0);
});

test('operators can correct a saved start with an explicit local time and offset', function (): void {
    // Arrange
    $fixture = liveShowFixture();
    $component = livewire(LiveShow::class, ['eventId' => $fixture['event']->id]);
    $component->call('item', $fixture['key'], 'start');
    $component->call('beginCorrection', $fixture['key'], 'correct');
    $corrected = now()->subMinutes(5)->startOfMinute();
    $local = $corrected->copy()->setTimezone($fixture['run']->content['timezone']);
    $component->set('reason', 'Started before I tapped the button');
    $component->set('actualStart', $local->format('Y-m-d\TH:i'));
    $component->set('startOffset', $local->format('P'));

    // Act
    $component->call('saveCorrection');

    // Assert
    $component->assertHasNoErrors()->assertSet('draftKey', '');
    expect($fixture['run']->refresh()->content['items'][0]['start'])->toBe($corrected->utc()->toIso8601String())
        ->and($fixture['run']->content['items'][0]['reason'])->toBe('Started before I tapped the button');
});

test('invalid correction offsets preserve the draft and saved start', function (): void {
    // Arrange
    $fixture = liveShowFixture();
    $component = livewire(LiveShow::class, ['eventId' => $fixture['event']->id]);
    $component->call('item', $fixture['key'], 'start');
    $saved = $fixture['run']->refresh()->content['items'][0]['start'];
    $component->call('beginCorrection', $fixture['key'], 'correct');
    $component->set('reason', 'Correct the tap time');
    $component->set('startOffset', '+25:00');

    // Act
    $component->call('saveCorrection');

    // Assert
    $component->assertHasErrors('run')->assertSet('draftKey', $fixture['key'])->assertSet('reason', 'Correct the tap time');
    expect($fixture['run']->refresh()->version)->toBe(2)
        ->and($fixture['run']->content['items'][0]['start'])->toBe($saved);
});

test('source review requires a removal reason and preserves checked in people', function (): void {
    // Arrange
    $fixture = liveShowFixture(segment: true);
    $component = livewire(LiveShow::class, ['eventId' => $fixture['event']->id]);
    $component->call('attendance', $fixture['person'], 'arrived', true);
    EventMatch::query()->where('event_id', $fixture['event']->id)->firstOrFail()->delete();
    $data = resolve(EventPackViewModel::class)->data($fixture['event']);
    $content = $data['content'];
    $content['segments'] = array_map(fn (array $segment): array => [...$segment, 'after_match_id' => null], $content['segments']);
    resolve(SaveEventPackAction::class)->handle($fixture['event'], 1, $data['card']['fingerprint'], EventPackData::fromValidated($content), true);

    // Act
    $component->call('beginReview');
    $component->call('acceptReview');

    // Assert
    $component->assertHasErrors('run')->assertSet('reviewing', true);
    expect($fixture['run']->refresh()->version)->toBe(2);

    // Act
    $component->set('removedReasons', [$fixture['key'] => 'Match cancelled']);
    $component->call('acceptReview');

    // Assert
    $component->assertHasNoErrors()->assertSet('reviewing', false);
    expect($fixture['run']->refresh()->version)->toBe(3)
        ->and($fixture['run']->content['items'][0]['state'])->toBe('skipped')
        ->and($fixture['run']->content['roster'][0]['arrived'])->not->toBeNull()
        ->and($fixture['run']->content['roster'][0]['assigned'])->toBeFalse();
});

test('cancelling a correction or review leaves the saved run unchanged', function (): void {
    // Arrange
    $fixture = liveShowFixture();
    $component = livewire(LiveShow::class, ['eventId' => $fixture['event']->id]);
    $component->call('beginCorrection', $fixture['key'], 'skip');
    $component->set('reason', 'Do not save this');

    // Act
    $component->call('cancelDraft');

    // Assert
    $component->assertSet('draftKey', '')->assertSet('reviewing', false);

    // Act
    $component->call('beginReview');
    $component->call('cancelDraft');

    // Assert
    $component->assertSet('reviewing', false);
    expect($fixture['run']->refresh()->version)->toBe(1)
        ->and($fixture['run']->content['items'][0]['state'])->toBe('pending');
});

test('a correction for an unknown item shows recovery feedback without opening a draft', function (): void {
    // Arrange
    $fixture = liveShowFixture();
    $component = livewire(LiveShow::class, ['eventId' => $fixture['event']->id]);

    // Act
    $component->call('beginCorrection', 'match:foreign', 'skip');

    // Assert
    $component->assertHasErrors('run')->assertSee(__('live-show.unknown_item'))->assertSet('draftKey', '');
    expect($fixture['run']->refresh()->version)->toBe(1);
});

test('completing from the live screen saves a read only show and removes live controls', function (): void {
    // Arrange
    $fixture = liveShowFixture();
    $component = livewire(LiveShow::class, ['eventId' => $fixture['event']->id]);
    $component->call('item', $fixture['key'], 'start');
    $component->call('item', $fixture['key'], 'finish');

    // Act
    $component->call('complete');

    // Assert
    $component->assertHasNoErrors()->assertSee('Show completed')->assertDontSee('Start item')->assertDontSee('Finish item')->assertDontSee('Complete show');
    expect($fixture['run']->refresh()->status)->toBe(LiveShowStatus::Completed)
        ->and($fixture['run']->completed_at)->not->toBeNull()->and($fixture['run']->version)->toBe(4);
});

test('a downgraded editor is refused every live show write and the run is unchanged', function (string $operation): void {
    // Arrange
    $promotion = Promotion::factory()->create();
    $event = Event::factory()->for($promotion)->create(['date' => now()]);
    $wrestlers = Wrestler::factory()->count(2)->for($promotion)->bookable()->create();
    EventMatch::factory()->for($event)->withCompetitors($wrestlers->all())->withReferees()->create();
    actingAs(administrator());
    $data = resolve(EventPackViewModel::class)->data($event);
    resolve(SaveEventPackAction::class)->handle($event, 0, $data['card']['fingerprint'], EventPackData::fromValidated($data['content']), false);
    $run = resolve(PrepareLiveShowAction::class)->handle($event, 0, 1, $data['card']['fingerprint'], signedInUser())->refresh();
    $fixture = ['event' => $event, 'run' => $run, 'key' => $run->content['items'][0]['key'], 'person' => $run->content['roster'][0]['key']];
    $user = basicUser();
    $promotion->users()->attach($user, ['role' => MembershipRole::Manager, 'status' => MembershipStatus::Active]);
    actingAs($user);
    get(route('events.live-show.show', $event))->assertOk();
    $component = livewire(LiveShow::class, ['eventId' => $event->id]);
    $promotion->users()->updateExistingPivot($user->id, ['role' => MembershipRole::Member]);
    resolve(PromotionContextService::class)->forgetMemberships();
    $before = $run->getAttributes();

    // Act
    $component->call($operation, ...match ($operation) {
        'attendance' => [$fixture['person'], 'arrived', true],
        'item' => [$fixture['key'], 'start'],
        default => [],
    });

    // Assert
    $component->assertForbidden();
    expect(EventLiveRun::query()->sole()->getAttributes())->toBe($before);
})->with(['prepare', 'attendance', 'item', 'saveCorrection', 'acceptReview', 'complete']);

test('a completed live show still names the venue after the venue is deleted', function (): void {
    // Arrange
    $venue = Venue::factory()
        ->create(['name' => 'Old Arena']);
    $fixture = liveShowFixture(venueId: $venue->id);
    $fixture['run']->forceFill(['status' => LiveShowStatus::Completed, 'completed_at' => now()])->save();
    $venue->delete();

    // Act
    $component = livewire(LiveShow::class, ['eventId' => $fixture['event']->id]);

    // Assert
    $component
        ->assertSee('Show completed')
        ->assertSee('Old Arena');
});

test('a running live show still names the venue after the venue is deleted', function (): void {
    // Arrange
    $venue = Venue::factory()
        ->create(['name' => 'Old Arena']);
    $fixture = liveShowFixture(venueId: $venue->id);
    $venue->delete();

    // Act
    $component = livewire(LiveShow::class, ['eventId' => $fixture['event']->id]);

    // Assert
    $component->assertSee('Old Arena');
});

test('the correction form opens only for operations that need a reason', function (string $operation, bool $opens): void {
    // Arrange
    $fixture = liveShowFixture();
    $component = livewire(LiveShow::class, ['eventId' => $fixture['event']->id]);

    // Act
    $component->call('beginCorrection', $fixture['key'], $operation);

    // Assert
    $component->assertSet('draftOperation', $opens ? $operation : '');

    if (! $opens) {
        $component->assertHasErrors('operation');
    }
})->with([
    'skip' => ['skip', true],
    'correct' => ['correct', true],
    'reopen' => ['reopen', true],
    'start' => ['start', false],
    'finish' => ['finish', false],
]);

test('a correction refused because the run changed keeps the draft and says the input is still there', function (): void {
    // Arrange
    $fixture = liveShowFixture();
    $component = livewire(LiveShow::class, ['eventId' => $fixture['event']->id]);
    $component->call('beginCorrection', $fixture['key'], 'skip');
    $component->set('reason', 'Keep my draft');
    livewire(LiveShow::class, ['eventId' => $fixture['event']->id])->call('item', $fixture['key'], 'start');

    // Act
    $component->call('saveCorrection');

    // Assert
    $component->assertHasErrors(['run' => 'This was changed by someone else. Reload to see the latest version, review it, then try again. Your changes are still here.'])
        ->assertSet('reason', 'Keep my draft');
});

test('a refused live action that is not a record change shows the plain message', function (): void {
    // Arrange
    $fixture = liveShowFixture();
    $component = livewire(LiveShow::class, ['eventId' => $fixture['event']->id]);

    // Act
    $component->call('complete');

    // Assert
    $component->assertHasErrors('run')->assertDontSee('Your changes are still here.');
});

test('an operation loads the event once before and once after the write', function (): void {
    // Arrange
    $fixture = liveShowFixture();
    $component = livewire(LiveShow::class, ['eventId' => $fixture['event']->id]);

    // Act
    $statements = recordStatements(fn () => $component->call('item', $fixture['key'], 'start'));

    // Assert
    $eventReads = array_filter($statements, fn (array $statement): bool => str_starts_with($statement['sql'], 'select * from "events" where "events"."id" = ?') && str_ends_with($statement['sql'], 'limit 1'));
    expect($eventReads)->toHaveCount(2);
});

test('the screen shows the event as the operation left it', function (): void {
    // Arrange
    $fixture = liveShowFixture();
    $complete = Double::for(CompleteLiveShowAction::class);
    $complete->expects('handle')->resolves(fn () => Event::query()->whereKey($fixture['event']->id)->update(['name' => 'Renamed During Save']));
    app()->instance(CompleteLiveShowAction::class, $complete);
    $component = livewire(LiveShow::class, ['eventId' => $fixture['event']->id]);

    // Act
    $component->call('complete');

    // Assert
    $component->assertSee('Renamed During Save');
    $complete->verify();
});

test('the item buttons refuse every operation except start and finish', function (string $operation): void {
    // Arrange
    $fixture = liveShowFixture();
    $before = $fixture['run']->refresh()->content;
    $component = livewire(LiveShow::class, ['eventId' => $fixture['event']->id]);

    // Act
    $component->call('item', $fixture['key'], $operation);

    // Assert
    $component->assertHasErrors('operation');
    expect($fixture['run']->refresh()->content)->toBe($before);
})->with(['skip', 'correct', 'reopen']);
