<?php

declare(strict_types=1);

use App\Enums\Promotions\MembershipRole;
use App\Enums\Promotions\MembershipStatus;
use App\Livewire\Stables\Modals\SplitModal;
use App\Models\Promotions\Promotion;
use App\Models\Roster\Stables\Stable;
use App\Models\Roster\Wrestlers\Wrestler;

use function Pest\Laravel\actingAs;
use function Pest\Livewire\livewire;

/**
 * An active stable with four wrestlers and one tag team, which is six members once a tag team counts as two.
 */
function splittableStable(?Promotion $promotion = null): Stable
{
    $stable = Stable::factory()->active()->for($promotion ?? Promotion::factory(), 'promotion')->create(['name' => 'Big Stable']);
    $stable->wrestlers()->attach(Wrestler::factory()->employed()->count(2)->create(), ['joined_at' => now()->subDay()]);

    return putStableMembersInPromotion($stable);
}

describe('split modal', function (): void {
    it('lists the current members and marks the unavailable ones with a reason', function (): void {
        // Arrange
        $stable = splittableStable();
        $injured = $stable->currentWrestlers()->firstOrFail();
        $injured->injuries()->create(['started_at' => now()->subHour()]);

        actingAs(administrator());

        // Act
        $modal = livewire(SplitModal::class, ['stableId' => $stable->id]);

        // Assert
        $modal
            ->assertSee($injured->name)
            ->assertSee('Injured')
            ->assertSeeHtml("id=\"form.wrestlerIds-{$injured->id}\"");
        expect($modal->instance()->wrestlers->firstWhere('id', $injured->id)['unavailability']?->value)->toBe('injured')
            ->and($modal->instance()->wrestlers->whereNotNull('unavailability'))->toHaveCount(1)
            ->and($modal->instance()->tagTeams)->toHaveCount(1);
    });

    it('splits the stable and names the new one for an owner', function (): void {
        // Arrange
        $promotion = Promotion::factory()->create();
        $stable = splittableStable($promotion);
        $movingWrestler = $stable->currentWrestlers()->firstOrFail();
        $movingTagTeam = $stable->currentTagTeams()->get()->firstOrFail();

        actingAsPromotionMember($promotion, MembershipRole::Owner);
        $modal = livewire(SplitModal::class, ['stableId' => $stable->id]);

        // Act
        $modal
            ->set('form.name', 'Breakaway')
            ->set('form.wrestlerIds', [(string) $movingWrestler->id])
            ->set('form.tagTeamIds', [(string) $movingTagTeam->id])
            ->call('save');

        // Assert
        $newStable = Stable::query()->where('name', 'Breakaway')->firstOrFail();
        $modal
            ->assertHasNoErrors()
            ->assertDispatched('stable-restructured')
            ->assertDispatched('closeModal')
            ->assertDispatched('flash-message', type: 'status', message: 'Big Stable was split. The new stable is Breakaway.');
        expect($newStable->promotion_id)->toBe($promotion->id)
            ->and($newStable->currentWrestlers()->pluck('wrestlers.id')->all())->toBe([$movingWrestler->id])
            ->and($newStable->currentTagTeams()->pluck('tag_teams.id')->all())->toBe([$movingTagTeam->id])
            ->and($stable->currentWrestlers()->whereKey($movingWrestler->id)->exists())->toBeFalse();
    });

    it('forbids a promotion member without the split ability', function (): void {
        // Arrange
        $promotion = Promotion::factory()->create();
        $stable = splittableStable($promotion);

        actingAsPromotionMember($promotion, MembershipRole::Member);

        // Act
        $modal = livewire(SplitModal::class, ['stableId' => $stable->id]);

        // Assert
        $modal->assertForbidden();
    });

    it('refuses to save after the manager lost the ability since opening the modal', function (): void {
        // Arrange
        $promotion = Promotion::factory()->create();
        $stable = splittableStable($promotion);
        $movingWrestler = $stable->currentWrestlers()->firstOrFail();
        $movingTagTeam = $stable->currentTagTeams()->get()->firstOrFail();

        $manager = actingAsPromotionMember($promotion, MembershipRole::Manager);
        $modal = livewire(SplitModal::class, ['stableId' => $stable->id]);
        $modal
            ->set('form.name', 'Breakaway')
            ->set('form.wrestlerIds', [$movingWrestler->id])
            ->set('form.tagTeamIds', [$movingTagTeam->id]);
        changePromotionMembership($promotion, $manager, MembershipRole::Member, MembershipStatus::Active);

        // Act
        $modal->call('save');

        // Assert
        $modal
            ->assertNotDispatched('stable-restructured')
            ->assertForbidden();
        expect(Stable::query()->where('name', 'Breakaway')->exists())->toBeFalse();
    });

    it('validates the new stable name', function (mixed $name, string $rule): void {
        // Arrange
        $stable = splittableStable();

        actingAs(administrator());
        $modal = livewire(SplitModal::class, ['stableId' => $stable->id]);

        // Act
        $modal
            ->set('form.name', $name)
            ->call('save');

        // Assert
        $modal
            ->assertNotDispatched('stable-restructured')
            ->assertHasErrors(['form.name' => $rule]);
    })->with([
        'missing' => ['', 'required'],
        'too long' => [str_repeat('a', 256), 'max'],
    ]);

    it('shows a business failure as a form error without splitting', function (string $failure): void {
        // Arrange
        $stable = splittableStable();
        $wrestlerKeys = $stable->currentWrestlers()->pluck('wrestlers.id')->all();
        $tagTeamKey = $stable->currentTagTeams()->get()->firstOrFail()->id;
        $stable->currentWrestlers()->whereKey($wrestlerKeys[0])->firstOrFail()->suspensions()->create(['started_at' => now()->subHour()]);
        [$wrestlerIds, $tagTeamIds, $message] = match ($failure) {
            'unavailable member' => [[$wrestlerKeys[0], $wrestlerKeys[1]], [$tagTeamKey], 'selected members are unavailable'],
            'leaving too few behind' => [[$wrestlerKeys[1], $wrestlerKeys[2], $wrestlerKeys[3]], [$tagTeamKey], 'the original stable would have 1 members'],
            'moving too few' => [[$wrestlerKeys[1]], [], 'the new stable would have 1 members'],
            default => [[], [], 'at least one member must be moved'],
        };

        actingAs(administrator());
        $modal = livewire(SplitModal::class, ['stableId' => $stable->id]);

        // Act
        $modal
            ->set('form.name', 'Breakaway')
            ->set('form.wrestlerIds', $wrestlerIds)
            ->set('form.tagTeamIds', $tagTeamIds)
            ->call('save');

        // Assert
        $modal
            ->assertHasErrors('stable')
            ->assertSee($message)
            ->assertNotDispatched('stable-restructured')
            ->assertNotDispatched('closeModal');
        expect(Stable::query()->where('name', 'Breakaway')->exists())->toBeFalse();
    })->with([
        'unavailable member',
        'leaving too few behind',
        'moving too few',
        'nothing selected',
    ]);

    it('rejects a name another active stable of the promotion already uses', function (): void {
        // Arrange
        $promotion = Promotion::factory()->create();
        $stable = splittableStable($promotion);
        Stable::factory()->active()->for($promotion, 'promotion')->create(['name' => 'Taken Name']);
        $movingWrestler = $stable->currentWrestlers()->firstOrFail();
        $movingTagTeam = $stable->currentTagTeams()->get()->firstOrFail();

        actingAs(administrator());
        $modal = livewire(SplitModal::class, ['stableId' => $stable->id]);

        // Act
        $modal
            ->set('form.name', 'Taken Name')
            ->set('form.wrestlerIds', [$movingWrestler->id])
            ->set('form.tagTeamIds', [$movingTagTeam->id])
            ->call('save');

        // Assert
        $modal
            ->assertHasErrors('stable')
            ->assertSee("an active stable named 'Taken Name' already exists");
    });
});
