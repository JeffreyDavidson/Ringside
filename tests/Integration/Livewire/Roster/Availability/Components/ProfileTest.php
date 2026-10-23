<?php

declare(strict_types=1);

use App\Actions\Roster\Availability\CreateAction;
use App\Data\Roster\UnavailabilityData;
use App\Enums\Promotions\MembershipRole;
use App\Enums\Promotions\MembershipStatus;
use App\Livewire\Roster\Availability\Components\Profile;
use App\Models\Events\Event;
use App\Models\Matches\EventMatch;
use App\Models\Promotions\Promotion;
use App\Models\Roster\Availability\Unavailability;
use App\Models\Roster\Referees\Referee;
use App\Models\Roster\Wrestlers\Wrestler;
use App\Models\Users\User;
use App\Services\Promotions\PromotionContextService;

use function Pest\Laravel\actingAs;
use function Pest\Livewire\livewire;

test('an operator reviews and saves unavailable dates on a profile', function (): void {
    // Arrange
    actingAs(administrator());
    $person = Wrestler::factory()->bookable()->for(Promotion::factory())->create();
    $component = livewire(Profile::class, ['kind' => 'wrestlers', 'personId' => $person->id]);

    // Act
    $component->set('form.startsOn', '2026-10-17');
    $component->set('form.endsOn', '2026-10-17');
    $component->set('form.reason', 'Outside commitment');
    $component->call('review');
    $component->call('save');

    // Assert
    $component->assertHasNoErrors()->assertSee('Unavailable dates saved');
    expect(Unavailability::query()->where('person_id', $person->id)->firstOrFail()->reason)->toBe('Outside commitment');
});

test('a referee profile saves dates and reports a repeated submission without duplicating it', function (): void {
    // Arrange
    actingAs(administrator());
    $person = Referee::factory()->for(Promotion::factory())->create();
    $component = livewire(Profile::class, ['kind' => 'referees', 'personId' => $person->id]);

    // Act
    foreach (range(1, 2) as $attempt) {
        $component->set('form.startsOn', '2026-10-17');
        $component->set('form.endsOn', '2026-10-17');
        $component->call('review');
        $component->call('save');
    }

    // Assert
    $component->assertHasErrors('form.startsOn')->assertSee('already recorded');
    expect(Unavailability::query()->where('person_type', 'referee')->count())->toBe(1);
});

test('an invalid roster kind returns not found instead of opening an availability form', function (): void {
    actingAs(administrator());
    livewire(Profile::class, ['kind' => 'managers', 'personId' => 1])->assertNotFound();
});

test('changing reviewed dates requires another review before anything is saved', function (): void {
    // Arrange
    actingAs(administrator());
    $person = Wrestler::factory()->for(Promotion::factory())->create();
    $component = livewire(Profile::class, ['kind' => 'wrestlers', 'personId' => $person->id]);
    $component->set('form.startsOn', '2026-10-17');
    $component->set('form.endsOn', '2026-10-17');
    $component->call('review');

    // Act
    $component->set('form.endsOn', '2026-10-18');
    $component->call('save');

    // Assert
    $component->assertHasErrors('form.startsOn');
    expect(Unavailability::query()->count())->toBe(0);
});

test('a profile cancellation requires the selected entry and preserves its history', function (): void {
    // Arrange
    $actor = administrator();
    actingAs($actor);
    $person = Wrestler::factory()->for(Promotion::factory())->create();
    $entry = resolve(CreateAction::class)->handle($person, new UnavailabilityData('2026-10-17', '2026-10-17', null), $actor);
    $component = livewire(Profile::class, ['kind' => 'wrestlers', 'personId' => $person->id]);

    // Act
    $component->call('requestCancel', $entry->id);
    $component->call('cancel');
    $component->call('cancel');

    // Assert
    $component->assertHasNoErrors()->assertSee('Unavailable entry cancelled')->assertSee('Cancelled');
    expect($entry->refresh()->cancelled_at)->not->toBeNull();
});

test('the review names affected dated and undated events while excluding unrelated dates', function (): void {
    // Arrange
    actingAs(administrator());
    $promotion = Promotion::factory()->create();
    $person = Wrestler::factory()->for($promotion)->create();
    $dated = Event::factory()->for($promotion)->create(['name' => 'Affected Saturday', 'date' => '2026-10-17 18:00:00']);
    $undated = Event::factory()->for($promotion)->create(['name' => 'Date Still Needed', 'date' => null]);
    $outside = Event::factory()->for($promotion)->create(['name' => 'Different Sunday', 'date' => '2026-10-18 18:00:00']);

    foreach ([$dated, $undated, $outside] as $event) {
        EventMatch::factory()->forEvent($event)->withCompetitors([$person])->create();
    }

    $component = livewire(Profile::class, ['kind' => 'wrestlers', 'personId' => $person->id]);

    // Act
    $component->set('form.startsOn', '2026-10-17');
    $component->set('form.endsOn', '2026-10-17');
    $component->call('review');

    // Assert
    $component->assertSee('Affected Saturday')->assertSee('Date Still Needed')->assertSee('date availability not checked')->assertDontSee('Different Sunday');

    // A booking made after the preview commits first and must appear in the refreshed retained-conflict list.
    $late = Event::factory()->for($promotion)->create(['name' => 'Booked While Form Open', 'date' => '2026-10-17 19:00:00']);
    EventMatch::factory()->forEvent($late)->withCompetitors([$person])->create();
    $component->call('save');
    $component->assertSee('Booked While Form Open')->assertSee('Affected Saturday')->assertDontSee('Different Sunday');
});

test('invalid calendar ranges and reasons are refused before anything is saved', function (string $start, string $end, string $reason, string $field): void {
    // Arrange
    actingAs(administrator());
    $person = Wrestler::factory()->for(Promotion::factory())->create();
    $component = livewire(Profile::class, ['kind' => 'wrestlers', 'personId' => $person->id]);

    // Act
    $component->set('form.startsOn', $start);
    $component->set('form.endsOn', $end);
    $component->set('form.reason', $reason);
    $component->call('save');

    // Assert
    $component->assertHasErrors("form.$field");
    expect(Unavailability::query()->count())->toBe(0);
})->with([
    'end before start' => ['2026-10-19', '2026-10-17', '', 'endsOn'],
    'impossible date' => ['2026-02-30', '2026-03-01', '', 'startsOn'],
    'not a date' => ['tomorrow', '2026-10-17', '', 'startsOn'],
    'missing end' => ['2026-10-17', '', '', 'endsOn'],
    'reason too long' => ['2026-10-17', '2026-10-17', str_repeat('x', 256), 'reason'],
]);

test('a view-only member sees availability but cannot review save or cancel through the component', function (string $method): void {
    // Arrange
    $promotion = Promotion::factory()->create();
    $person = Wrestler::factory()->for($promotion)->create();
    $entry = resolve(CreateAction::class)->handle($person, new UnavailabilityData('2026-10-17', '2026-10-17', null), administrator());
    $member = User::factory()->create();
    $promotion->users()->attach($member, ['role' => MembershipRole::Member, 'status' => MembershipStatus::Active]);
    $context = app(PromotionContextService::class);
    $context->set($promotion);
    $context->enforce();
    actingAs($member);
    $component = livewire(Profile::class, ['kind' => 'wrestlers', 'personId' => $person->id]);

    // Act
    $component->call($method, ...($method === 'requestCancel' ? [$entry->id] : []));

    // Assert
    $component->assertForbidden();
    expect(Unavailability::query()->count())->toBe(1)->and($entry->refresh()->cancelled_at)->toBeNull();
})->with(['review', 'save', 'requestCancel', 'cancel']);
