<?php

declare(strict_types=1);

use App\Enums\Promotions\MembershipRole;
use App\Enums\Promotions\MembershipStatus;
use App\Livewire\Stables\Modals\MergeModal;
use App\Models\Promotions\Promotion;
use App\Models\Roster\Stables\Stable;

use function Pest\Laravel\actingAs;
use function Pest\Livewire\livewire;

describe('merge modal', function (): void {
    it('offers only the other active unretired stables of the same promotion', function (): void {
        // Arrange
        $promotion = Promotion::factory()->create();
        $stable = Stable::factory()->active()->for($promotion, 'promotion')->create(['name' => 'Current Stable']);
        Stable::factory()->active()->for($promotion, 'promotion')->create(['name' => 'Eligible Stable']);
        Stable::factory()->inactive()->for($promotion, 'promotion')->create(['name' => 'Disbanded Stable']);
        Stable::factory()->retired()->for($promotion, 'promotion')->create(['name' => 'Retired Stable']);
        Stable::factory()->active()->for(Promotion::factory(), 'promotion')->create(['name' => 'Foreign Stable']);
        Stable::factory()->active()->create(['name' => 'Unowned Stable']);

        actingAs(administrator());

        // Act
        $modal = livewire(MergeModal::class, ['stableId' => $stable->id]);

        // Assert
        $modal
            ->assertSee('Eligible Stable')
            ->assertDontSee('Current Stable</option>', false)
            ->assertDontSee('Disbanded Stable')
            ->assertDontSee('Retired Stable')
            ->assertDontSee('Foreign Stable')
            ->assertDontSee('Unowned Stable');
        expect($modal->instance()->candidates->pluck('name')->all())->toBe(['Eligible Stable']);
    });

    it('describes which stable keeps its name once the other is picked', function (): void {
        // Arrange
        $stable = Stable::factory()->active()->create(['name' => 'Current Stable']);
        $other = Stable::factory()->active()->create(['name' => 'Absorbed Stable']);

        actingAs(administrator());
        $modal = livewire(MergeModal::class, ['stableId' => $stable->id]);

        // Act
        $modal->set('form.otherStableId', $other->id);

        // Assert
        $modal->assertSee('Current Stable keeps its name and receives Absorbed Stable’s members; Absorbed Stable ends and is removed.');
    });

    it('merges the picked stable into this one for an owner', function (): void {
        // Arrange
        $promotion = Promotion::factory()->create();
        $stable = Stable::factory()->active()->for($promotion, 'promotion')->create();
        $other = Stable::factory()->active()->for($promotion, 'promotion')->create();
        putStableMembersInPromotion($stable);
        putStableMembersInPromotion($other);
        $otherWrestlerIds = $other->currentWrestlers()->pluck('wrestlers.id')->all();

        actingAsPromotionMember($promotion, MembershipRole::Owner);
        $modal = livewire(MergeModal::class, ['stableId' => $stable->id]);

        // Act
        $modal
            ->set('form.otherStableId', $other->id)
            ->call('save');

        // Assert
        $modal
            ->assertHasNoErrors()
            ->assertDispatched('stable-restructured')
            ->assertDispatched('closeModal')
            ->assertDispatched('flash-message', type: 'status', message: "{$other->name} was merged into {$stable->name}.");
        expect($stable->currentWrestlers()->pluck('wrestlers.id')->all())->toContain(...$otherWrestlerIds)
            ->and($other->refresh()->trashed())->toBeTrue();
    });

    it('shows a concurrent membership change as a form error instead of failing', function (): void {
        // Arrange
        $stable = Stable::factory()->active()->create();
        $other = Stable::factory()->active()->create();
        rivalStableClaimsWrestlerOnNextMembershipInsert($other->currentWrestlers()->firstOrFail());

        actingAs(administrator());
        $modal = livewire(MergeModal::class, ['stableId' => $stable->id]);

        // Act
        $modal
            ->set('form.otherStableId', $other->id)
            ->call('save');

        // Assert
        $modal
            ->assertHasErrors('stable')
            ->assertSee('This stable or one of its members was changed at the same time. Refresh the page and try again.')
            ->assertNotDispatched('stable-restructured')
            ->assertNotDispatched('closeModal');
        expect($other->refresh()->trashed())->toBeFalse()
            ->and($other->currentWrestlers()->exists())->toBeTrue();
    });

    it('forbids a promotion member without the merge ability', function (): void {
        // Arrange
        $promotion = Promotion::factory()->create();
        $stable = Stable::factory()->active()->for($promotion, 'promotion')->create();

        actingAsPromotionMember($promotion, MembershipRole::Member);

        // Act
        $modal = livewire(MergeModal::class, ['stableId' => $stable->id]);

        // Assert
        $modal->assertForbidden();
    });

    it('refuses to save after the manager lost the ability since opening the modal', function (): void {
        // Arrange
        $promotion = Promotion::factory()->create();
        $stable = Stable::factory()->active()->for($promotion, 'promotion')->create();
        $other = Stable::factory()->active()->for($promotion, 'promotion')->create();

        $manager = actingAsPromotionMember($promotion, MembershipRole::Manager);
        $modal = livewire(MergeModal::class, ['stableId' => $stable->id]);
        $modal->set('form.otherStableId', $other->id);
        changePromotionMembership($promotion, $manager, MembershipRole::Member, MembershipStatus::Active);

        // Act
        $modal->call('save');

        // Assert
        $modal
            ->assertNotDispatched('stable-restructured')
            ->assertForbidden();
        expect($other->refresh()->trashed())->toBeFalse();
    });

    it('requires a stable to be picked', function (): void {
        // Arrange
        $stable = Stable::factory()->active()->create();
        Stable::factory()->active()->create();

        actingAs(administrator());
        $modal = livewire(MergeModal::class, ['stableId' => $stable->id]);

        // Act
        $modal->call('save');

        // Assert
        $modal
            ->assertNotDispatched('stable-restructured')
            ->assertHasErrors(['form.otherStableId' => 'required'])
            ->assertSee('The stable field is required.');
    });

    it('rejects a stable that was not offered', function (string $state): void {
        // Arrange
        $promotion = Promotion::factory()->create();
        $stable = Stable::factory()->active()->for($promotion, 'promotion')->create();
        $notOffered = match ($state) {
            'foreign promotion' => Stable::factory()->active()->for(Promotion::factory(), 'promotion')->create(),
            'disbanded' => Stable::factory()->inactive()->for($promotion, 'promotion')->create(),
            default => $stable,
        };

        actingAs(administrator());
        $modal = livewire(MergeModal::class, ['stableId' => $stable->id]);

        // Act
        $modal
            ->set('form.otherStableId', $notOffered->id)
            ->call('save');

        // Assert
        $modal
            ->assertNotDispatched('stable-restructured')
            ->assertHasErrors(['form.otherStableId' => 'in']);
        expect($notOffered->refresh()->trashed())->toBeFalse();
    })->with([
        'foreign promotion',
        'disbanded',
        'itself',
    ]);

    it('shows a business failure as a form error without changing either stable', function (): void {
        // Arrange
        $stable = Stable::factory()->active()->create();
        $other = Stable::factory()->active()->create();
        $other->currentWrestlers()->firstOrFail()->suspensions()->create(['started_at' => now()->subHour()]);

        actingAs(administrator());
        $modal = livewire(MergeModal::class, ['stableId' => $stable->id]);

        // Act
        $modal
            ->set('form.otherStableId', $other->id)
            ->call('save');

        // Assert
        $modal
            ->assertHasErrors('stable')
            ->assertSee('these secondary stable members are unavailable')
            ->assertNotDispatched('stable-restructured')
            ->assertNotDispatched('closeModal');
        expect($other->refresh()->trashed())->toBeFalse();
    });

    it('explains when there is no other stable to merge with', function (): void {
        // Arrange
        $stable = Stable::factory()->active()->create();

        actingAs(administrator());

        // Act
        $modal = livewire(MergeModal::class, ['stableId' => $stable->id]);

        // Assert
        $modal
            ->assertSee('There are no other active stables in this promotion to merge with.')
            ->assertDontSeeHtml('data-test="save-merge"');
    });
});
