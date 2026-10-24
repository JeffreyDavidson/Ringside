<?php

declare(strict_types=1);

use App\Actions\Crew\ArchiveCrewContactAction;
use App\Actions\Crew\SaveCrewContactAction;
use App\Data\Crew\CrewContactData;
use App\Enums\Promotions\MembershipRole;
use App\Enums\Promotions\MembershipStatus;
use App\Exceptions\RecordChangedException;
use App\Models\Crew\CrewContact;
use App\Models\Promotions\Promotion;
use App\Services\Promotions\PromotionContextService;

use function Pest\Laravel\actingAs;

test('retrying a contact create saves one record without replacing it', function (): void {
    // Arrange
    $promotion = Promotion::factory()->create();
    $user = basicUser();
    $promotion->users()->attach($user, ['role' => MembershipRole::Manager, 'status' => MembershipStatus::Active]);
    actingAs($user);
    resolve(PromotionContextService::class)->set($promotion);
    resolve(PromotionContextService::class)->enforce();
    $action = resolve(SaveCrewContactAction::class);
    $client = 'eed36a82-90cb-4a79-b2cc-92e6c4051001';

    // Act
    $saved = $action->handle(null, 0, $client, new CrewContactData('Alex Sound', null, null, null), signedInUser());
    $retry = $action->handle(null, 0, $client, new CrewContactData('Changed retry', null, null, null), signedInUser());

    // Assert
    expect($retry->id)->toBe($saved->id)
        ->and(CrewContact::query()->count())->toBe(1)
        ->and($saved->refresh()->name)->toBe('Alex Sound');
});

test('a stale edit cannot replace an archived crew contact', function (): void {
    // Arrange
    actingAs(administrator());
    $contact = CrewContact::factory()->create(['name' => 'Alex Sound']);
    $stale = clone $contact;
    $archive = resolve(ArchiveCrewContactAction::class);

    // Act
    $archive->handle($contact, 1, signedInUser());

    // Assert
    expect(fn () => resolve(SaveCrewContactAction::class)->handle(
        $stale,
        1,
        $stale->client_id,
        new CrewContactData('Wrong update', null, null, null),
        signedInUser(),
    ))
        ->toThrow(RecordChangedException::class)
        ->and($contact->refresh()->archived_at)->not->toBeNull()
        ->and($contact->name)->toBe('Alex Sound');
});

test('repeated archive and invalid new-contact version cannot change records', function (): void {
    // Arrange
    actingAs(administrator());
    $promotion = Promotion::factory()->create();
    resolve(PromotionContextService::class)->set($promotion);
    resolve(PromotionContextService::class)->enforce();
    $contact = CrewContact::factory()->for($promotion)->create();
    $archive = resolve(ArchiveCrewContactAction::class);
    $archive->handle($contact, 1, signedInUser());

    // Act / Assert
    expect(fn () => $archive->handle($contact, 1, signedInUser()))->toThrow(RecordChangedException::class);
    expect(fn () => resolve(SaveCrewContactAction::class)->handle(
        null,
        5,
        'eed36a82-90cb-4a79-b2cc-92e6c4051888',
        new CrewContactData('New contact', null, null, null),
        signedInUser(),
    ))->toThrow(RecordChangedException::class)
        ->and(CrewContact::query()->count())->toBe(1)
        ->and($contact->refresh()->version)->toBe(2);
});

test('saving a contact names the actor passed in as the audit causer', function (): void {
    // Arrange
    actingAs(administrator());
    $actor = administrator();
    $contact = CrewContact::factory()->create();
    $since = lastActivityId();

    // Act
    resolve(SaveCrewContactAction::class)->handle($contact, 1, $contact->client_id, new CrewContactData('Alex Sound', null, null, null), $actor);

    // Assert
    expect(activityEntriesAfter($since, 'production'))->toBe(['contact-save|'.causerTag($actor)]);
});
