<?php

declare(strict_types=1);

use App\Actions\Crew\ArchiveCrewContactAction;
use App\Enums\Promotions\MembershipRole;
use App\Enums\Promotions\MembershipStatus;
use App\Models\Crew\CrewContact;
use App\Models\Promotions\Promotion;
use App\Services\Promotions\PromotionContextService;
use Illuminate\Auth\Access\AuthorizationException;

use function Pest\Laravel\actingAs;

test('archive permissions preserve the contact and its private details', function (MembershipRole $role, bool $allowed): void {
    // Arrange
    $promotion = Promotion::factory()->create();
    $contact = CrewContact::factory()->for($promotion)->create(['notes' => 'Retain this note']);
    $user = basicUser();
    $promotion->users()->attach($user, ['role' => $role, 'status' => MembershipStatus::Active]);
    actingAs($user);
    resolve(PromotionContextService::class)->set($promotion);
    resolve(PromotionContextService::class)->enforce();
    $action = resolve(ArchiveCrewContactAction::class);

    // Act / Assert
    if (! $allowed) {
        expect(fn () => $action->handle($contact, 1))->toThrow(AuthorizationException::class)
            ->and($contact->refresh()->archived_at)->toBeNull();

        return;
    }
    $action->handle($contact, 1);
    expect($contact->refresh()->archived_at)->not->toBeNull()
        ->and($contact->notes)->toBe('Retain this note')
        ->and($contact->version)->toBe(2);
})->with(['owner' => [MembershipRole::Owner, true], 'manager' => [MembershipRole::Manager, true], 'member' => [MembershipRole::Member, false]]);
