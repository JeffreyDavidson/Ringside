<?php

declare(strict_types=1);

use App\Actions\TagTeams\EmployAction;
use App\Actions\TagTeams\ReleaseAction;
use App\Data\TagTeams\TagTeamMembershipData;
use App\Enums\Shared\EmploymentStatus;
use App\Exceptions\Roster\TagTeams\CannotBeEmployedException;
use App\Models\Roster\TagTeams\TagTeam;
use App\Models\Roster\Wrestlers\Wrestler;
use App\Services\Roster\TagTeams\TagTeamMembershipService;
use Illuminate\Support\Facades\Date;

test('released tag teams require a renewed membership before re-employment', function () {
    // Arrange
    $tagTeam = TagTeam::factory()->unemployed()->create();
    $transitionedAt = Date::now();

    resolve(EmployAction::class)
        ->handle($tagTeam, $transitionedAt);
    resolve(ReleaseAction::class)
        ->handle(freshModel($tagTeam), $transitionedAt);
    $released = freshModel($tagTeam);

    // Act
    $reEmploy = fn () => resolve(EmployAction::class)
        ->handle($released, $transitionedAt);

    // Assert
    expect($released->status)->toBe(EmploymentStatus::Released)
        ->and($released->currentEmployment()->exists())->toBeFalse()
        ->and($reEmploy)->toThrow(CannotBeEmployedException::class);
});

test('renewed memberships support distinct tag team employment periods', function () {
    // Arrange
    $tagTeam = TagTeam::factory()->unemployed()->create();
    $membershipService = resolve(TagTeamMembershipService::class);

    // Act
    resolve(EmployAction::class)
        ->handle($tagTeam, Date::now()->subYear());
    resolve(ReleaseAction::class)
        ->handle(freshModel($tagTeam), Date::now()->subMonths(9));

    $membershipService->establishMembership(
        $tagTeam,
        new TagTeamMembershipData(Wrestler::factory()->count(2)->create()),
        Date::now()->subMonths(8),
    );
    resolve(EmployAction::class)
        ->handle(freshModel($tagTeam), Date::now()->subMonths(6));
    resolve(ReleaseAction::class)
        ->handle(freshModel($tagTeam), Date::now()->subMonths(3));

    $membershipService->establishMembership(
        $tagTeam,
        new TagTeamMembershipData(Wrestler::factory()->count(2)->create()),
        Date::now()->subMonths(2),
    );
    resolve(EmployAction::class)
        ->handle(freshModel($tagTeam), Date::now()->subMonth());
    $employed = freshModel($tagTeam);

    // Assert
    expect($employed->status)->toBe(EmploymentStatus::Employed)
        ->and($employed->currentEmployment()->exists())->toBeTrue()
        ->and($employed->employments()->count())->toBe(3)
        ->and($employed->previousEmployments()->count())->toBe(2)
        ->and($employed->employments()->whereNull('ended_at')->count())->toBe(1);
});
