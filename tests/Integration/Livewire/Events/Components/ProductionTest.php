<?php

declare(strict_types=1);

use App\Actions\Events\SaveEventPackAction;
use App\Actions\Events\SaveEventProductionAction;
use App\Data\Events\EventPackData;
use App\Data\Events\EventProductionData;
use App\Enums\Promotions\MembershipRole;
use App\Enums\Promotions\MembershipStatus;
use App\Livewire\Events\Components\Production;
use App\Models\Crew\CrewContact;
use App\Models\Events\Event;
use App\Models\Matches\EventMatch;
use App\Models\Promotions\Promotion;
use App\Services\Promotions\PromotionContextService;
use App\ViewModels\Events\EventPackViewModel;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;
use function Pest\Livewire\livewire;

test('an authorized pack editor adds a crew assignment and saves a cue', function (): void {
    // Arrange
    actingAs(administrator());
    $event = Event::factory()->for(Promotion::factory())->create();
    $match = EventMatch::factory()->for($event)->create();
    $contact = CrewContact::factory()->create(['promotion_id' => $event->promotion_id, 'name' => 'Alex Operator']);
    $page = livewire(Production::class, ['eventId' => $event->id]);

    // Act
    $page->call('addAssignment')->set('assignments.0.contact_id', $contact->id)->set('assignments.0.role', 'sound')
        ->call('addCue', 'match:'.$match->id)->set('cues.0.subject', 'Harbor Hero')->set('cues.0.trigger', 'Caller signal')
        ->set('cues.0.instruction', 'Play entrance')->call('save');

    // Assert
    $page->assertHasNoErrors()->assertSee('Crew and cues saved.');
    expect(data_get($event->pack()->firstOrFail()->content, 'production.assignments.0.name'))->toBe('Alex Operator')
        ->and(data_get($event->pack()->firstOrFail()->content, 'production.cues.0.instruction'))->toBe('Play entrance');
});

test('an editor deliberately reattaches a retained cue to a current item', function (): void {
    // Arrange
    require_once __DIR__.'/../../../../Fixtures/production.php';
    $fixture = productionFixture();
    $event = $fixture['event'];
    $fixture['match']->delete();
    $replacement = EventMatch::factory()->for($event)->create();
    $data = resolve(EventPackViewModel::class)->data($event);
    resolve(SaveEventPackAction::class)->handle($event, 1, $data['card']['fingerprint'], EventPackData::fromValidated($data['content']), true);
    $id = $fixture['production']['cues'][0]['id'];
    $page = livewire(Production::class, ['eventId' => $event->id]);

    // Act
    $page->set('retainedTargets.'.$id, 'match:'.$replacement->id)->call('restoreCue', $id)->call('save');

    // Assert
    $page->assertHasNoErrors();
    expect(data_get($event->pack()->firstOrFail()->content, 'production.cues.0.item_key'))->toBe('match:'.$replacement->id);
});

test('cue reorder removal and name refresh preserve the saved identities', function (): void {
    // Arrange
    require_once __DIR__.'/../../../../Fixtures/production.php';
    $fixture = productionFixture();
    $event = $fixture['event'];
    $fixture['contact']->name = 'Refreshed crew';
    $fixture['contact']->save();
    $page = livewire(Production::class, ['eventId' => $event->id]);
    $first = $fixture['production']['cues'][0]['id'];
    $owner = $fixture['production']['assignments'][0]['id'];

    // Act / Assert
    $page->call('addCue', 'match:'.$fixture['match']->id)->set('cues.1.subject', 'Second cue')
        ->set('cues.1.trigger', 'After bell')->set('cues.1.instruction', 'Fade music');
    $second = data_get($page->get('cues'), '1.id');
    expect($second)->toBeString();
    $page->call('moveCue', $first, -1)->call('moveCue', 'unknown', 1);
    expect(data_get($page->get('cues'), '0.id'))->toBe($first);
    $page->call('moveCue', $first, 1);
    expect(data_get($page->get('cues'), '1.id'))->toBe($first);
    $page->call('removeCue', $first)->call('refreshName', $owner)->call('save')->assertHasNoErrors();
    expect(data_get($event->pack()->firstOrFail()->content, 'production.cues.0.subject'))->toBe('Second cue')
        ->and(data_get($event->pack()->firstOrFail()->content, 'production.assignments.0.name'))->toBe('Refreshed crew');
    $page->call('removeAssignment', $owner)->call('save')->assertHasNoErrors();
    expect(data_get($event->pack()->firstOrFail()->content, 'production.assignments.0.active'))->toBeFalse();
});

test('the editor refuses more than the approved assignment and cue limits', function (): void {
    // Arrange
    require_once __DIR__.'/../../../../Fixtures/production.php';
    $fixture = productionFixture();
    $page = livewire(Production::class, ['eventId' => $fixture['event']->id]);
    $assignments = array_map(fn (int $index): array => [...$fixture['production']['assignments'][0], 'id' => sprintf('eed36a82-90cb-4a79-b2cc-%012d', $index)], range(1, 100));
    $cues = array_map(fn (int $index): array => [...$fixture['production']['cues'][0], 'id' => sprintf('eed36a82-90cb-4a79-b2cc-%012d', $index)], range(1, 20));

    // Act / Assert
    $page->set('assignments', $assignments)->call('addAssignment')->assertHasErrors('production');
    expect($page->get('assignments'))->toHaveCount(100);
    $page->set('cues', $cues)->call('addCue', 'match:'.$fixture['match']->id)->assertHasErrors('production');
    expect($page->get('cues'))->toHaveCount(20);
});

test('the editor refuses malformed production input and saves nothing', function (string $case, string $field): void {
    // Arrange
    require_once __DIR__.'/../../../../Fixtures/production.php';
    $fixture = productionFixture();
    $event = $fixture['event'];
    $before = $event->pack()->firstOrFail()->content;
    $page = livewire(Production::class, ['eventId' => $event->id]);
    $assignments = $page->get('assignments');
    $cues = $page->get('cues');

    if ($case === 'duplicate assignment') {
        $assignments[] = $assignments[0];
    }

    if ($case === 'duplicate cue') {
        $cues[] = $cues[0];
    }

    if ($case === 'unsafe url') {
        $cues[0]['url'] = 'javascript:alert(1)';
    }

    // Act
    $page->set('assignments', $assignments)
        ->set('cues', $cues)
        ->call('save');

    // Assert
    $page->assertHasErrors([$field]);
    expect($event->pack()->firstOrFail()->content)->toBe($before)
        ->and($event->pack()->firstOrFail()->version)->toBe(1);
})->with([
    'duplicate assignment' => ['duplicate assignment', 'assignments.1.id'],
    'duplicate cue' => ['duplicate cue', 'cues.1.id'],
    'unsafe url' => ['unsafe url', 'cues.0.url'],
]);

test('a stale production editor keeps its draft and requires reload', function (): void {
    // Arrange
    require_once __DIR__.'/../../../../Fixtures/production.php';
    $fixture = productionFixture();
    $event = $fixture['event'];
    $page = livewire(Production::class, ['eventId' => $event->id]);
    $pack = $event->pack()->firstOrFail();
    resolve(SaveEventProductionAction::class)->handle($event, 1, $pack->card_fingerprint, EventProductionData::fromValidated($fixture['production']), false, administrator());

    // Act
    $page->set('notes', 'Unsaved private draft')->call('save');

    // Assert
    $page->assertHasErrors(['production' => 'This was changed by someone else. Reload to see the latest version, review it, then try again. Your changes are still here.'])->assertSet('notes', 'Unsaved private draft');
    expect($event->pack()->firstOrFail()->version)->toBe(2);
    $page->call('reloadSaved')->assertSet('notes', 'PRIVATE-NOTE-7Q')->assertSet('version', 2);
});

test('a member forging the production component is forbidden', function (): void {
    // Arrange
    require_once __DIR__.'/../../../../Fixtures/production.php';
    $fixture = productionFixture();
    $event = $fixture['event'];
    $user = basicUser();
    Promotion::query()->findOrFail($event->promotion_id)->users()->attach($user, ['role' => MembershipRole::Member, 'status' => MembershipStatus::Active]);
    actingAs($user);
    get(route('events.pack.show', $event))->assertOk();

    // Act
    $component = livewire(Production::class, ['eventId' => $event->id]);

    // Assert
    $component->assertForbidden();
});

test('a downgraded editor cannot save the production plan', function (): void {
    // Arrange
    require_once __DIR__.'/../../../../Fixtures/production.php';
    $fixture = productionFixture();
    $event = $fixture['event'];
    $promotion = Promotion::query()->findOrFail($event->promotion_id);
    $user = basicUser();
    $promotion->users()->attach($user, ['role' => MembershipRole::Manager, 'status' => MembershipStatus::Active]);
    actingAs($user);
    get(route('events.pack.show', $event))->assertOk();
    $component = livewire(Production::class, ['eventId' => $event->id])->set('notes', 'Forged note');
    $promotion->users()->updateExistingPivot($user->id, ['role' => MembershipRole::Member]);
    resolve(PromotionContextService::class)->forgetMemberships();
    $before = $event->pack()->firstOrFail()->getAttributes();

    // Act
    $component->call('save');

    // Assert
    $component->assertForbidden();
    expect($event->pack()->firstOrFail()->getAttributes())->toBe($before);
});
