<?php

declare(strict_types=1);

use App\Actions\Events\PrepareLiveShowAction;
use App\Actions\Events\SaveEventProductionAction;
use App\Enums\Promotions\MembershipRole;
use App\Enums\Promotions\MembershipStatus;
use App\Livewire\Crew\Components\Directory;
use App\Livewire\Events\Components\Production;
use App\Models\Crew\CrewContact;
use App\Models\Events\Event;
use App\Models\Events\EventPack;
use App\Models\Promotions\Promotion;
use App\ViewModels\EventPackViewModel;
use App\ViewModels\LiveShowViewModel;
use Illuminate\Auth\Access\AuthorizationException;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;

test('a member pack response omits private production content before rendering', function (): void {
    // Arrange
    $promotion = Promotion::factory()->create();
    $event = Event::factory()->for($promotion)->create();
    $pack = EventPack::factory()->for($event)->create();
    $pack->content = [...$pack->content, 'production' => ['notes' => 'CREW-PRIVATE-7Q', 'assignments' => [], 'cues' => []]];
    $pack->save();
    $member = basicUser();
    $promotion->users()->attach($member, ['role' => MembershipRole::Member, 'status' => MembershipStatus::Active]);
    actingAs($member);

    // Act
    $response = get(route('events.pack.show', $event));

    // Assert
    $response->assertOk()->assertDontSee('CREW-PRIVATE-7Q');
    expect($response->viewData('content'))->not->toHaveKey('production')
        ->and(data_get($response->viewData('pack'), 'content'))->not->toHaveKey('production');
});

test('a member live response removes crew cues and counts from its view data', function (): void {
    // Arrange
    require_once __DIR__.'/../../Fixtures/production.php';
    $fixture = productionFixture();
    $event = $fixture['event'];
    $data = resolve(EventPackViewModel::class)->data($event);
    resolve(PrepareLiveShowAction::class)->handle($event, 0, 1, $data['card']['fingerprint']);
    $member = basicUser();
    Promotion::query()->findOrFail($event->promotion_id)->users()->attach($member, ['role' => MembershipRole::Member, 'status' => MembershipStatus::Active]);
    actingAs($member);

    // Act
    $response = get(route('events.live-show.show', $event));

    // Assert
    $response->assertOk()->assertDontSee('PRIVATE-CREW-7Q')->assertDontSee('PRIVATE-CUE-7Q');
    $screen = resolve(LiveShowViewModel::class)->data($event);
    expect(json_encode(data_get($screen, 'run.content')))->not->toContain('PRIVATE-CREW-7Q', 'PRIVATE-CUE-7Q')
        ->and(data_get($screen, 'run.content'))->not->toHaveKey('production')
        ->and($screen['current'])->toBeNull()
        ->and($screen['next'])->not->toHaveKey('cues');
});

test('production endpoints remain private for members and scoped to the active promotion', function (MembershipRole $role, int $status): void {
    // Arrange
    require_once __DIR__.'/../../Fixtures/production.php';
    $fixture = productionFixture();
    $event = $fixture['event'];
    $promotion = Promotion::query()->findOrFail($event->promotion_id);
    $user = basicUser();
    $promotion->users()->attach($user, ['role' => $role, 'status' => MembershipStatus::Active]);
    actingAs($user);

    // Act / Assert
    get(route('crew.contacts.index'))->assertStatus($status);
    get(route('events.production-sheet.show', $event))->assertStatus($status);
    get(route('events.production-sheet.show', ['event' => $event, 'print' => 1]))->assertStatus($status);
    $other = Event::factory()->for(Promotion::factory())->create();
    get(route('events.production-sheet.show', $other))->assertNotFound();
})->with(['owner' => [MembershipRole::Owner, 200], 'manager' => [MembershipRole::Manager, 200], 'member' => [MembershipRole::Member, 403]]);

test('member forged production updates and directory edits write nothing', function (): void {
    // Arrange
    require_once __DIR__.'/../../Fixtures/production.php';
    $fixture = productionFixture();
    $event = $fixture['event'];
    $user = basicUser();
    Promotion::query()->findOrFail($event->promotion_id)->users()->attach($user, ['role' => MembershipRole::Member, 'status' => MembershipStatus::Active]);
    actingAs($user);
    get(route('events.pack.show', $event))->assertOk();
    $before = $event->pack()->firstOrFail()->content;

    // Act / Assert
    \Pest\Livewire\livewire(Directory::class)->assertForbidden();
    \Pest\Livewire\livewire(Production::class, ['eventId' => $event->id])->assertForbidden();
    expect(fn () => resolve(SaveEventProductionAction::class)->handle($event, 1,
        $event->pack()->firstOrFail()->card_fingerprint, $fixture['production'], false))
        ->toThrow(AuthorizationException::class)
        ->and($event->pack()->firstOrFail()->content)->toBe($before);
});

test('published event cards and results omit crew contacts and production cues', function (): void {
    // Arrange
    $world = publicEventFixture();
    $event = $world['event'];
    $contact = CrewContact::factory()->create(['promotion_id' => $event->promotion_id,
        'name' => 'PRIVATE-CREW-7Q', 'phone' => 'PRIVATE-PHONE-7Q',
        'email' => 'private-crew-7q@example.test', 'notes' => 'PRIVATE-CONTACT-NOTE-7Q']);
    $data = resolve(EventPackViewModel::class)->data($event);
    resolve(SaveEventProductionAction::class)->handle($event, 0, $data['card']['fingerprint'], [
        'notes' => 'PRIVATE-NOTE-7Q',
        'assignments' => [['id' => 'eed36a82-90cb-4a79-b2cc-92e6c4051001', 'contact_id' => $contact->id, 'role' => 'sound', 'timezone' => 'UTC']],
        'cues' => [['id' => 'eed36a82-90cb-4a79-b2cc-92e6c4051003', 'item_key' => 'match:'.$world['match']->id,
            'subject' => 'PRIVATE-SUBJECT-7Q', 'trigger' => 'PRIVATE-TRIGGER-7Q', 'instruction' => 'PRIVATE-CUE-7Q',
            'owner_id' => 'eed36a82-90cb-4a79-b2cc-92e6c4051001']],
    ], false);

    // Act
    savePublicDraft($event);
    releasePublicPage($event);
    $publication = releasePublicPage($event, 'results');
    $surfaces = publicEventSurfaces($event, $publication);

    // Assert
    $surfaces['snapshots'] = json_encode([$publication->card, $publication->results], JSON_THROW_ON_ERROR);
    expect($surfaces)->each->not->toContain('PRIVATE-CREW-7Q', 'PRIVATE-PHONE-7Q', 'private-crew-7q@example.test', 'PRIVATE-CONTACT-NOTE-7Q', 'PRIVATE-NOTE-7Q', 'PRIVATE-SUBJECT-7Q', 'PRIVATE-TRIGGER-7Q', 'PRIVATE-CUE-7Q')
        ->and($surfaces['card'])->toContain('Robin North', 'Casey Shore')
        ->and($surfaces['results'])->toContain('Robin North');
});
