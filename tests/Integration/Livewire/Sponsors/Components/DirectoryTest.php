<?php

declare(strict_types=1);

use App\Actions\Sponsors\SaveSponsorAction;
use App\Data\Sponsors\SponsorData;
use App\Enums\Promotions\MembershipRole;
use App\Enums\Promotions\MembershipStatus;
use App\Livewire\Sponsors\Components\Directory;
use App\Models\Events\EventSponsorCommitment;
use App\Models\Promotions\Promotion;
use App\Models\Sponsors\Sponsor;
use App\Services\Promotions\PromotionContextService;

use function Pest\Laravel\actingAs;
use function Pest\Livewire\livewire;

test('the directory saves a private reusable sponsor', function (): void {
    // Arrange
    $promotion = Promotion::factory()->create();
    actingAs(administrator());
    $component = livewire(Directory::class, ['promotionId' => $promotion->id]);
    $component->call('newSponsor');

    // Act
    $component->set('form.name', 'Harbor Hardware')->set('form.email', 'alex@example.test')->call('save');

    // Assert
    $component->assertHasNoErrors()->assertSee('Sponsor saved.');
    expect(Sponsor::query()->firstOrFail()->name)->toBe('Harbor Hardware');
});

test('editing and archiving a sponsor keeps private details and its event history', function (): void {
    // Arrange
    $promotion = Promotion::factory()->create();
    $sponsor = Sponsor::factory()->create(['promotion_id' => $promotion->id, 'name' => 'Harbor Hardware', 'contact_name' => 'Alex', 'contact_email' => 'alex@example.test', 'contact_phone' => '555-0100', 'notes' => 'Private agreement']);
    $promise = EventSponsorCommitment::factory()->create(['promotion_id' => $promotion->id, 'sponsor_id' => $sponsor->id]);
    actingAs(administrator());
    $component = livewire(Directory::class, ['promotionId' => $promotion->id, 'sponsorId' => $sponsor->id]);

    // Act
    $component->call('edit', $sponsor->id)->set('form.archived', true)->call('save');

    // Assert
    $component->assertHasNoErrors()->assertSee('Sponsor saved.')->assertSee('Private agreement')->assertSee('Event promises for this sponsor');
    expect($sponsor->refresh()->archived_at)->not->toBeNull()->and($sponsor->contact_email)->toBe('alex@example.test');
    $component->call('edit', $sponsor->id)->set('form.archived', false)->call('save')->assertHasNoErrors();
    expect($sponsor->refresh()->archived_at)->toBeNull();
    $component->call('cancelDraft')->assertSet('formShown', false);
});

test('a stale directory form preserves its entered details', function (): void {
    // Arrange
    $promotion = Promotion::factory()->create();
    $sponsor = Sponsor::factory()->create(['promotion_id' => $promotion->id]);
    actingAs(administrator());
    $component = livewire(Directory::class, ['promotionId' => $promotion->id]);
    $component->call('edit', $sponsor->id);
    app(SaveSponsorAction::class)->handle($promotion, $sponsor->id, 1, $sponsor->submission_key, new SponsorData('Renamed Harbor'));

    // Act
    $component->set('form.name', 'My draft')->call('save');

    // Assert
    $component->assertHasErrors(['save' => 'This was changed by someone else. Reload to see the latest version, review it, then try again. Your changes are still here.'])->assertSet('form.name', 'My draft');
    expect($sponsor->refresh()->name)->toBe('Renamed Harbor');
});

test('a membership downgrade refuses a new sponsor opened as manager', function (): void {
    // Arrange
    $promotion = Promotion::factory()->create();
    $user = basicUser();
    $promotion->users()->attach($user, ['role' => MembershipRole::Manager, 'status' => MembershipStatus::Active]);
    actingAs($user);
    $context = app(PromotionContextService::class);
    $context->set($promotion);
    $context->enforce();
    $component = livewire(Directory::class, ['promotionId' => $promotion->id]);
    $component->call('newSponsor')->set('form.name', 'Cannot save');
    $promotion->users()->updateExistingPivot($user->id, ['role' => MembershipRole::Member]);
    $context->forgetMemberships();

    // Act
    $component->call('save');

    // Assert
    $component->assertForbidden();
    expect(Sponsor::query()->count())->toBe(0);
});

test('a membership downgrade refuses an edit opened as manager', function (): void {
    // Arrange
    $promotion = Promotion::factory()->create();
    $sponsor = Sponsor::factory()->create(['promotion_id' => $promotion->id, 'name' => 'Harbor Hardware']);
    $user = basicUser();
    $promotion->users()->attach($user, ['role' => MembershipRole::Manager, 'status' => MembershipStatus::Active]);
    actingAs($user);
    $context = app(PromotionContextService::class);
    $context->set($promotion);
    $context->enforce();
    $component = livewire(Directory::class, ['promotionId' => $promotion->id]);
    $component->call('edit', $sponsor->id)->set('form.name', 'Cannot save');
    $promotion->users()->updateExistingPivot($user->id, ['role' => MembershipRole::Member]);
    $context->forgetMemberships();

    // Act
    $component->call('save');

    // Assert
    $component->assertForbidden();
    expect($sponsor->refresh()->name)->toBe('Harbor Hardware')
        ->and($sponsor->version)->toBe(1);
});

test('an administrator in an enforced promotion cannot open another promotions directory', function (): void {
    // Arrange
    $owned = Promotion::factory()->create();
    $foreign = Promotion::factory()->create();
    actingAs(administrator());
    $context = app(PromotionContextService::class);
    $context->set($owned);
    $context->enforce();

    // Act
    $component = livewire(Directory::class, ['promotionId' => $foreign->id]);

    // Assert
    $component->assertNotFound();
    expect(Sponsor::query()->count())->toBe(0);
});
