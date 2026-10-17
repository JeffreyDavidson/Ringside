<?php

declare(strict_types=1);

use App\Actions\Events\PrepareLiveShowAction;
use App\Actions\Events\SaveEventProductionAction;
use App\Data\Events\EventProductionData;
use App\Enums\Promotions\MembershipRole;
use App\Enums\Promotions\MembershipStatus;
use App\Models\Crew\CrewContact;
use App\Models\Events\Event;
use App\Models\Events\EventLiveRun;
use App\Models\Events\EventPack;
use App\Models\Promotions\Promotion;
use App\ViewModels\Events\EventPackViewModel;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;

require_once __DIR__.'/../../../../Fixtures/production.php';

test('long cue sheets retain full manual instructions and never print contact details', function (): void {
    // Arrange
    $fixture = productionFixture();
    $event = $fixture['event'];
    $fixture['contact']->phone = 'CONTACT-PHONE-7Q';
    $fixture['contact']->notes = 'CONTACT-NOTE-7Q';
    $fixture['contact']->save();
    $input = $fixture['production'];
    $input['cues'] = array_map(fn (int $i): array => [
        ...$input['cues'][0],
        'id' => sprintf('eed36a82-90cb-4a79-b2cc-%012d', $i),
        'subject' => 'Long cue '.$i,
        'instruction' => str_repeat('Hold the entrance until the caller gives the signal. Fade under the announcement and keep the next entrance ready. ', 12),
    ], range(1, 6));
    resolve(SaveEventProductionAction::class)->handle($event, 1, $event->pack()
        ->firstOrFail()->card_fingerprint, EventProductionData::fromValidated($input), false);

    // Act
    $response = get(route('events.production-sheet.show', [
        'event' => $event,
        'print' => 1,
    ]));

    // Assert
    $response
        ->assertOk()
        ->assertViewIs('events.production-print')
        ->assertSee($input['cues'][5]['instruction'])
        ->assertSee('Long cue 6')
        ->assertDontSee('CONTACT-PHONE-7Q')
        ->assertDontSee('CONTACT-NOTE-7Q');
});

test('opening an empty production sheet creates no data and labels the plan unsaved', function (): void {
    // Arrange
    actingAs(administrator());
    $event = Event::factory()
        ->create();

    // Act
    $response = get(route('events.production-sheet.show', $event));

    // Assert
    $response
        ->assertOk()
        ->assertViewIs('events.production')
        ->assertViewHas('sheet', fn (array $sheet): bool => $sheet['event']->is($event) && $sheet['source'] === 'plan' && $sheet['version'] === 0)
        ->assertSee('Not saved');

    expect(EventPack::query()
        ->count())->toBe(0)
        ->and(EventLiveRun::query()
            ->count())
        ->toBe(0)
        ->and(CrewContact::query()
            ->count())
        ->toBe(0);
});

test('invalid production filters return validation errors', function (string $field, mixed $value): void {
    // Arrange
    actingAs(administrator());
    $event = Event::factory()
        ->create();

    // Act
    $response = get(route('events.production-sheet.show', [
        'event' => $event,
        $field => $value,
    ]));

    // Assert
    $response
        ->assertRedirect()
        ->assertSessionHasErrors($field)
        ->assertSessionHasInput($field, $value);

    expect(EventPack::query()
        ->count())->toBe(0);
})->with([
    'source' => ['source', 'invalid'],
    'person' => ['person', 'bad'],
    'role' => ['role', 'invalid'],
    'print' => ['print', 'bad'],
    'source array' => ['source', ['plan']],
    'person array' => ['person', [1]],
    'role array' => ['role', ['announcer']],
    'print array' => ['print', [1]],
]);

test('guests cannot open production sheets', function (): void {
    // Arrange
    $event = Event::factory()
        ->create();

    // Act
    $response = get(route('events.production-sheet.show', $event));

    // Assert
    $response
        ->assertRedirect(route('login'));
});

test('production print defaults to the adopted live snapshot', function (): void {
    // Arrange
    $fixture = productionFixture();
    $event = $fixture['event'];
    $data = resolve(EventPackViewModel::class)->data($event);
    resolve(PrepareLiveShowAction::class)->handle($event, 0, 1, $data['card']['fingerprint']);
    $production = $fixture['production'];
    $production['cues'][0]['instruction'] = 'UNADOPTED-CUE-7Q';
    resolve(SaveEventProductionAction::class)->handle($event, 1, $data['card']['fingerprint'], EventProductionData::fromValidated($production), false);

    // Act
    $response = get(route('events.production-sheet.show', ['event' => $event, 'print' => 1]));

    // Assert
    $response
        ->assertOk()
        ->assertViewIs('events.production-print')
        ->assertViewHas('sheet', fn (array $sheet): bool => $sheet['event']->is($event) && $sheet['source'] === 'live')
        ->assertSee('PRIVATE-CUE-7Q')
        ->assertDontSee('UNADOPTED-CUE-7Q')
        ->assertDontSee('PRIVATE-NOTE-7Q');
});

test('production print explicitly selects the unadopted plan', function (): void {
    // Arrange
    $fixture = productionFixture();
    $event = $fixture['event'];
    $data = resolve(EventPackViewModel::class)->data($event);
    resolve(PrepareLiveShowAction::class)->handle($event, 0, 1, $data['card']['fingerprint']);
    $production = $fixture['production'];
    $production['cues'][0]['instruction'] = 'UNADOPTED-CUE-7Q';
    resolve(SaveEventProductionAction::class)->handle($event, 1, $data['card']['fingerprint'], EventProductionData::fromValidated($production), false);

    // Act
    $response = get(route('events.production-sheet.show', ['event' => $event, 'print' => 1, 'source' => 'plan']));

    // Assert
    $response
        ->assertOk()
        ->assertViewIs('events.production-print')
        ->assertViewHas('sheet', fn (array $sheet): bool => $sheet['event']->is($event) && $sheet['source'] === 'plan')
        ->assertSee('UNADOPTED-CUE-7Q')
        ->assertDontSee('PRIVATE-CUE-7Q')
        ->assertDontSee('PRIVATE-NOTE-7Q');
});

test('the requested person retains their assignments and cues', function (): void {
    // Arrange
    $fixture = productionFixture();
    $event = $fixture['event'];
    $parameters = ['person' => $fixture['contact']->id];

    // Act
    $response = get(route('events.production-sheet.show', ['event' => $event, ...$parameters]));

    // Assert
    $response
        ->assertOk()
        ->assertViewIs('events.production');

    $sheet = $response->viewData('sheet');
    expect(data_get($sheet, 'event.id'))->toBe($event->id)
        ->and(data_get($sheet, 'assignments.*.contact_id'))
        ->toBe([$fixture['contact']->id])
        ->and(data_get($sheet, 'person'))
        ->toBe($fixture['contact']->id)
        ->and(data_get($sheet, 'role'))
        ->toBeNull()
        ->and(data_get($sheet, 'rows.0.cues'))
        ->toHaveCount(1);
});

test('another person excludes unrelated assignments and cues', function (): void {
    // Arrange
    $fixture = productionFixture();
    $event = $fixture['event'];
    $parameters = ['person' => 999999];

    // Act
    $response = get(route('events.production-sheet.show', ['event' => $event, ...$parameters]));

    // Assert
    $response
        ->assertOk()
        ->assertViewIs('events.production');

    $sheet = $response->viewData('sheet');
    expect(data_get($sheet, 'event.id'))->toBe($event->id)
        ->and(data_get($sheet, 'assignments.*.contact_id'))
        ->toBe([])
        ->and(data_get($sheet, 'person'))
        ->toBe(999999)
        ->and(data_get($sheet, 'role'))
        ->toBeNull()
        ->and(data_get($sheet, 'rows.0.cues'))
        ->toBeEmpty();
});

test('the requested role retains its assignments and cues', function (): void {
    // Arrange
    $fixture = productionFixture();
    $event = $fixture['event'];
    $parameters = ['role' => 'sound'];

    // Act
    $response = get(route('events.production-sheet.show', ['event' => $event, ...$parameters]));

    // Assert
    $response
        ->assertOk()
        ->assertViewIs('events.production');

    $sheet = $response->viewData('sheet');
    expect(data_get($sheet, 'event.id'))->toBe($event->id)
        ->and(data_get($sheet, 'assignments.*.contact_id'))
        ->toBe([$fixture['contact']->id])
        ->and(data_get($sheet, 'person'))
        ->toBeNull()
        ->and(data_get($sheet, 'role'))
        ->toBe('sound')
        ->and(data_get($sheet, 'rows.0.cues'))
        ->toHaveCount(1);
});

test('another role excludes unrelated assignments and cues', function (): void {
    // Arrange
    $fixture = productionFixture();
    $event = $fixture['event'];
    $parameters = ['role' => 'announcer'];

    // Act
    $response = get(route('events.production-sheet.show', ['event' => $event, ...$parameters]));

    // Assert
    $response
        ->assertOk()
        ->assertViewIs('events.production');

    $sheet = $response->viewData('sheet');
    expect(data_get($sheet, 'event.id'))->toBe($event->id)
        ->and(data_get($sheet, 'assignments.*.contact_id'))
        ->toBe([])
        ->and(data_get($sheet, 'person'))
        ->toBeNull()
        ->and(data_get($sheet, 'role'))
        ->toBe('announcer')
        ->and(data_get($sheet, 'rows.0.cues'))
        ->toBeEmpty();
});

test('owners and managers can read production sheets without creating data', function (MembershipRole $role): void {
    // Arrange
    $promotion = Promotion::factory()
        ->create();
    $event = Event::factory()
        ->for($promotion)
        ->create();

    actingAsPromotionMember($promotion, $role);

    // Act
    $response = get(route('events.production-sheet.show', $event));

    // Assert
    $response
        ->assertOk()
        ->assertViewIs('events.production')
        ->assertViewHas('sheet', fn (array $sheet): bool => $sheet['event']->is($event));

    expect(EventPack::query()
        ->count())->toBe(0)
        ->and(EventLiveRun::query()
            ->count())
        ->toBe(0);
})->with(['owner' => MembershipRole::Owner, 'manager' => MembershipRole::Manager]);

test('active members cannot read production sheets', function (): void {
    // Arrange
    $promotion = Promotion::factory()
        ->create();
    $event = Event::factory()
        ->for($promotion)
        ->create();

    actingAsPromotionMember($promotion, MembershipRole::Member);

    // Act
    $response = get(route('events.production-sheet.show', $event));

    // Assert
    $response
        ->assertForbidden();

    expect(EventPack::query()
        ->count())->toBe(0)
        ->and(EventLiveRun::query()
            ->count())
        ->toBe(0);
});

test('accounts without memberships cannot read production sheets', function (): void {
    // Arrange
    $promotion = Promotion::factory()
        ->create();
    $event = Event::factory()
        ->for($promotion)
        ->create();

    actingAs(basicUser());

    // Act
    $response = get(route('events.production-sheet.show', $event));

    // Assert
    $response
        ->assertForbidden()
        ->assertViewIs('promotions.no-membership');

    expect(EventPack::query()
        ->count())->toBe(0)
        ->and(EventLiveRun::query()
            ->count())
        ->toBe(0);
});

test('foreign owners cannot read production sheets', function (): void {
    // Arrange
    $promotion = Promotion::factory()
        ->create();
    $event = Event::factory()
        ->for($promotion)
        ->create();
    $other = Promotion::factory()
        ->create();

    actingAsPromotionMember($other, MembershipRole::Owner);

    // Act
    $response = get(route('events.production-sheet.show', $event));

    // Assert
    $response
        ->assertNotFound();

    expect(EventPack::query()
        ->count())->toBe(0)
        ->and(EventLiveRun::query()
            ->count())
        ->toBe(0);
});

test('missing events have no production sheet', function (): void {
    // Arrange
    actingAs(administrator());

    // Act
    $response = get(route('events.production-sheet.show', 999999));

    // Assert
    $response
        ->assertNotFound();
});

test('deleted events have no production sheet', function (): void {
    // Arrange
    $promotion = Promotion::factory()
        ->create();
    $event = Event::factory()
        ->for($promotion)
        ->create();
    $event->delete();

    actingAs(administrator());

    // Act
    $response = get(route('events.production-sheet.show', $event->id));

    // Assert
    $response
        ->assertNotFound();
});

test('empty optional filters preserve the unsaved plan without creating records', function (array $filters): void {
    // Arrange
    $event = Event::factory()
        ->create();

    actingAs(administrator());

    $requestData = ['event' => $event,
        ...$filters];

    // Act
    $response = get(route('events.production-sheet.show', $requestData));

    // Assert
    $response
        ->assertOk()
        ->assertViewIs('events.production')
        ->assertViewHas('sheet', fn (array $sheet): bool => $sheet['event']->is($event) && $sheet['source'] === 'plan' && $sheet['person'] === null && $sheet['role'] === null);

    expect(EventPack::query()
        ->count())->toBe(0)
        ->and(EventLiveRun::query()
            ->count())->toBe(0)
        ->and(CrewContact::query()
            ->count())->toBe(0);
})->with([
    'omitted filters' => [[]],
    'empty filters' => [
        [
            'source' => '',
            'person' => '',
            'role' => '',
            'print' => '',
        ],
    ],
]);

test('a valid unmatched person filter returns an empty assignment selection', function (): void {
    // Arrange
    $fixture = productionFixture();
    $event = $fixture['event'];
    $unmatched = CrewContact::factory()
        ->create();
    $originalContent = $event->pack()
        ->firstOrFail()->content;

    $requestData = [
        'event' => $event,
        'person' => $unmatched->id,
    ];

    // Act
    $response = get(route('events.production-sheet.show', $requestData));

    // Assert
    $response
        ->assertOk()
        ->assertViewIs('events.production')
        ->assertViewHas('sheet', fn (array $sheet): bool => $sheet['person'] === $unmatched->id && $sheet['assignments'] === []);

    expect($event->pack()
        ->firstOrFail()->content)->toBe($originalContent);
});

test('production sheet access follows the viewer\'s promotion role', function (MembershipRole $role, int $status): void {
    // Arrange
    $event = productionFixture()['event'];

    $user = basicUser();
    Promotion::query()
        ->findOrFail($event->promotion_id)
        ->users()
        ->attach($user, ['role' => $role, 'status' => MembershipStatus::Active]);
    actingAs($user);

    // Act
    $response = get(route('events.production-sheet.show', $event));

    // Assert
    $response->assertStatus($status);
})->with([
    'owner' => [MembershipRole::Owner, 200],
    'manager' => [MembershipRole::Manager, 200],
    'member' => [MembershipRole::Member, 403],
]);

test('printable production sheet access follows the viewer\'s promotion role', function (MembershipRole $role, int $status): void {
    // Arrange
    $event = productionFixture()['event'];

    $user = basicUser();
    Promotion::query()
        ->findOrFail($event->promotion_id)
        ->users()
        ->attach($user, ['role' => $role, 'status' => MembershipStatus::Active]);
    actingAs($user);

    // Act
    $response = get(route('events.production-sheet.show', ['event' => $event, 'print' => 1]));

    // Assert
    $response->assertStatus($status);
})->with([
    'owner' => [MembershipRole::Owner, 200],
    'manager' => [MembershipRole::Manager, 200],
    'member' => [MembershipRole::Member, 403],
]);

test('a production sheet from another promotion is not found', function (MembershipRole $role): void {
    // Arrange
    $event = productionFixture()['event'];
    $foreignEvent = Event::factory()
        ->for(Promotion::factory())
        ->create();

    $user = basicUser();
    Promotion::query()
        ->findOrFail($event->promotion_id)
        ->users()
        ->attach($user, ['role' => $role, 'status' => MembershipStatus::Active]);
    actingAs($user);

    // Act
    $response = get(route('events.production-sheet.show', $foreignEvent));

    // Assert
    $response->assertNotFound();
})->with([
    'owner' => [MembershipRole::Owner],
    'manager' => [MembershipRole::Manager],
    'member' => [MembershipRole::Member],
]);
