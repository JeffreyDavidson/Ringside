<?php

declare(strict_types=1);

use App\Enums\Promotions\MembershipRole;
use App\Livewire\Stables\Modals\FormModal as StableFormModal;
use App\Livewire\TagTeams\Modals\FormModal as TagTeamFormModal;
use App\Models\Promotions\Promotion;
use App\Models\Roster\Managers\Manager;
use App\Models\Roster\Stables\Stable;
use App\Models\Roster\TagTeams\TagTeam;
use App\Models\Roster\Wrestlers\Wrestler;

use function Pest\Laravel\actingAs;
use function Pest\Livewire\livewire;

describe('scoping the roster search to the form promotion', function (): void {
    beforeEach(function (): void {
        actingAs(administrator());
    });

    it('offers an administrator without an enforced context only the edited tag team promotion', function (): void {
        // Arrange
        $promotion = Promotion::factory()->create();
        $otherPromotion = Promotion::factory()->create();
        Wrestler::factory()->create(['name' => 'Ours Wrestler', 'promotion_id' => $promotion->id]);
        Wrestler::factory()->create(['name' => 'Theirs Wrestler', 'promotion_id' => $otherPromotion->id]);
        Manager::factory()->create(['first_name' => 'Ours', 'last_name' => 'Manager', 'promotion_id' => $promotion->id]);
        Manager::factory()->create(['first_name' => 'Theirs', 'last_name' => 'Manager', 'promotion_id' => $otherPromotion->id]);
        $tagTeam = TagTeam::factory()->create(['promotion_id' => $promotion->id]);
        $modal = livewire(TagTeamFormModal::class);
        $modal->call('openModal', $tagTeam->id);

        // Act
        $wrestlers = $modal->instance()->searchRoster('wrestlers', '');
        $managers = $modal->instance()->searchRoster('managers', '');

        // Assert
        expect(array_column($wrestlers, 'name'))->toBe(['Ours Wrestler'])
            ->and(array_column($managers, 'name'))->toBe(['Ours Manager']);
    });

    it('offers an administrator without an enforced context only the edited stable promotion', function (): void {
        // Arrange
        $promotion = Promotion::factory()->create();
        $otherPromotion = Promotion::factory()->create();
        Wrestler::factory()->create(['name' => 'Ours Wrestler', 'promotion_id' => $promotion->id]);
        Wrestler::factory()->create(['name' => 'Theirs Wrestler', 'promotion_id' => $otherPromotion->id]);
        TagTeam::factory()->create(['name' => 'Ours Team', 'promotion_id' => $promotion->id]);
        TagTeam::factory()->create(['name' => 'Theirs Team', 'promotion_id' => $otherPromotion->id]);
        $stable = Stable::factory()->create(['promotion_id' => $promotion->id]);
        $modal = livewire(StableFormModal::class);
        $modal->call('openModal', $stable->id);

        // Act
        $wrestlers = $modal->instance()->searchRoster('wrestlers', '');
        $tagTeams = $modal->instance()->searchRoster('tag_teams', '');

        // Assert
        expect(array_column($wrestlers, 'name'))->toBe(['Ours Wrestler'])
            ->and(array_column($tagTeams, 'name'))->toBe(['Ours Team']);
    });

    it('offers an administrator creating without an enforced context only unowned records', function (): void {
        // Arrange
        $promotion = Promotion::factory()->create();
        $otherPromotion = Promotion::factory()->create();
        Wrestler::factory()->create(['name' => 'Unowned Wrestler', 'promotion_id' => null]);
        Wrestler::factory()->create(['name' => 'Owned Wrestler', 'promotion_id' => $promotion->id]);
        $modal = livewire(TagTeamFormModal::class);

        // Act
        $wrestlers = $modal->instance()->searchRoster('wrestlers', '');

        // Assert
        expect(array_column($wrestlers, 'name'))->toBe(['Unowned Wrestler']);
    });

    it('offers a member in an enforced context exactly that promotion', function (): void {
        // Arrange
        $promotion = Promotion::factory()->create();
        $otherPromotion = Promotion::factory()->create();
        actingAsPromotionMember($promotion, MembershipRole::Owner);
        Wrestler::factory()->create(['name' => 'Ours Wrestler', 'promotion_id' => $promotion->id]);
        Wrestler::factory()->create(['name' => 'Theirs Wrestler', 'promotion_id' => $otherPromotion->id]);
        $modal = livewire(TagTeamFormModal::class);

        // Act
        $wrestlers = $modal->instance()->searchRoster('wrestlers', '');

        // Assert
        expect(array_column($wrestlers, 'name'))->toBe(['Ours Wrestler']);
    });

    it('labels selected ids of the form promotion including deleted ones but not other promotions', function (): void {
        // Arrange
        $promotion = Promotion::factory()->create();
        $otherPromotion = Promotion::factory()->create();
        $deleted = Wrestler::factory()->trashed()->create(['name' => 'Ours Deleted', 'promotion_id' => $promotion->id]);
        $foreign = Wrestler::factory()->create(['name' => 'Theirs Wrestler', 'promotion_id' => $otherPromotion->id]);
        $tagTeam = TagTeam::factory()->create(['promotion_id' => $promotion->id]);
        $modal = livewire(TagTeamFormModal::class);
        $modal->call('openModal', $tagTeam->id);
        $modal->set('form.wrestlerA', $deleted->id);
        $modal->set('form.wrestlerB', $foreign->id);

        // Act
        $labels = $modal->instance()->selectedRosterLabels['wrestlers'];

        // Assert
        expect(array_column($labels, 'name'))->toBe(['Ours Deleted']);
    });
});
