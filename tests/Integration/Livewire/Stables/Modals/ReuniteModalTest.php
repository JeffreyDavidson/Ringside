<?php

declare(strict_types=1);

use App\Enums\Promotions\MembershipRole;
use App\Enums\Promotions\MembershipStatus;
use App\Enums\Stables\StableStatus;
use App\Livewire\Stables\Modals\ReuniteModal;
use App\Models\Promotions\Promotion;
use App\Models\Roster\Stables\Stable;
use App\Models\Roster\Wrestlers\Wrestler;

use function Pest\Laravel\actingAs;
use function Pest\Livewire\livewire;

describe('reunite modal', function (): void {
    it('lists the available former members, all checked', function (): void {
        // Arrange
        $stable = Stable::factory()->disbanded()->create();
        $wrestlerIds = $stable->previousWrestlers()->pluck('wrestlers.id')->all();
        $tagTeamIds = $stable->previousTagTeams()->pluck('tag_teams.id')->all();

        actingAs(administrator());

        // Act
        $modal = livewire(ReuniteModal::class, ['stableId' => $stable->id]);

        // Assert
        $modal
            ->assertSet('form.wrestlerIds', $wrestlerIds)
            ->assertSet('form.tagTeamIds', $tagTeamIds);
        foreach ($stable->previousWrestlers()->get() as $wrestler) {
            $modal->assertSee($wrestler->name);
        }
    });

    it('reunites the stable with the checked members for an owner', function (): void {
        // Arrange
        $promotion = Promotion::factory()->create();
        $stable = Stable::factory()->disbanded()->for($promotion, 'promotion')->create();
        $stable->wrestlers()->attach(Wrestler::factory()->employed()->create(), [
            'joined_at' => now()->subDays(2),
            'left_at' => now()->subDay(),
        ]);
        putStableMembersInPromotion($stable);
        $leftBehind = $stable->previousWrestlers()->withoutGlobalScopes()->firstOrFail();

        actingAsPromotionMember($promotion, MembershipRole::Owner);
        $modal = livewire(ReuniteModal::class, ['stableId' => $stable->id]);

        // Act
        $modal
            ->set('form.wrestlerIds', array_values(array_diff($modal->get('form.wrestlerIds'), [$leftBehind->id])))
            ->call('save');

        // Assert
        $modal
            ->assertHasNoErrors()
            ->assertDispatched('stable-restructured')
            ->assertDispatched('closeModal')
            ->assertDispatched('flash-message', type: 'status', message: "{$stable->name} was reunited.");
        expect($stable->refresh()->status)->toBe(StableStatus::Active)
            ->and($stable->currentWrestlers()->whereKey($leftBehind->id)->exists())->toBeFalse()
            ->and($stable->currentWrestlers()->count())->toBe(2)
            ->and($stable->currentTagTeams()->count())->toBe(1);
    });

    it('forbids a promotion member without the reunite ability', function (): void {
        // Arrange
        $promotion = Promotion::factory()->create();
        $stable = Stable::factory()->disbanded()->for($promotion, 'promotion')->create();

        actingAsPromotionMember($promotion, MembershipRole::Member);

        // Act
        $modal = livewire(ReuniteModal::class, ['stableId' => $stable->id]);

        // Assert
        $modal->assertForbidden();
    });

    it('refuses to save after the manager lost the ability since opening the modal', function (): void {
        // Arrange
        $promotion = Promotion::factory()->create();
        $stable = Stable::factory()->disbanded()->for($promotion, 'promotion')->create();
        putStableMembersInPromotion($stable);

        $manager = actingAsPromotionMember($promotion, MembershipRole::Manager);
        $modal = livewire(ReuniteModal::class, ['stableId' => $stable->id]);
        changePromotionMembership($promotion, $manager, MembershipRole::Member, MembershipStatus::Active);

        // Act
        $modal->call('save');

        // Assert
        $modal
            ->assertNotDispatched('stable-restructured')
            ->assertForbidden();
        expect($stable->currentActivityPeriod()->exists())->toBeFalse();
    });

    it('shows a business failure as a form error without reuniting', function (): void {
        // Arrange
        $stable = Stable::factory()->disbanded()->create();

        actingAs(administrator());
        $modal = livewire(ReuniteModal::class, ['stableId' => $stable->id]);

        // Act
        $modal
            ->set('form.wrestlerIds', [])
            ->set('form.tagTeamIds', [])
            ->call('save');

        // Assert
        $modal
            ->assertHasErrors('stable')
            ->assertSee('at least 3 are required')
            ->assertNotDispatched('stable-restructured')
            ->assertNotDispatched('closeModal');
        expect($stable->currentActivityPeriod()->exists())->toBeFalse();
    });

    it('rejects a member who was never a former member', function (): void {
        // Arrange
        $stable = Stable::factory()->disbanded()->create();
        $outsider = Wrestler::factory()->employed()->create();

        actingAs(administrator());
        $modal = livewire(ReuniteModal::class, ['stableId' => $stable->id]);

        // Act
        $modal
            ->set('form.wrestlerIds', [...$modal->get('form.wrestlerIds'), $outsider->id])
            ->call('save');

        // Assert
        $modal
            ->assertHasErrors('stable')
            ->assertSee("not available former members: {$outsider->name}");
        expect($stable->currentActivityPeriod()->exists())->toBeFalse();
    });
});
