<?php

declare(strict_types=1);

use App\Enums\Promotions\MembershipRole;
use App\Enums\Promotions\MembershipStatus;
use App\Models\Events\Event;
use App\Models\Promotions\Promotion;
use App\Models\Roster\Managers\Manager;
use App\Models\Roster\Referees\Referee;
use App\Models\Roster\Stables\Stable;
use App\Models\Roster\TagTeams\TagTeam;
use App\Models\Roster\Wrestlers\Wrestler;
use App\Models\Titles\Title;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;
use function Pest\Laravel\withSession;

test('a record from another promotion looks the same as a missing record', function (string $modelClass, string $routeName, MembershipRole $role): void {
    // Arrange
    $user = basicUser();
    $ownPromotion = Promotion::factory()->create();
    $otherPromotion = Promotion::factory()->create();
    $ownPromotion->users()->attach($user, ['role' => $role->value, 'status' => MembershipStatus::Active->value]);
    $foreignRecord = $modelClass::factory()->for($otherPromotion, 'promotion')->create();
    actingAs($user);
    withSession(['active_promotion_id' => $ownPromotion->id]);

    // Act
    $foreignResponse = get(route($routeName, $foreignRecord->getKey()));
    $missingResponse = get(route($routeName, 999_999));

    // Assert
    $foreignResponse->assertNotFound();
    $missingResponse->assertNotFound();
})->with([
    'wrestler' => [Wrestler::class, 'wrestlers.show'],
    'manager' => [Manager::class, 'managers.show'],
    'referee' => [Referee::class, 'referees.show'],
    'tag team' => [TagTeam::class, 'tag-teams.show'],
    'stable' => [Stable::class, 'stables.show'],
    'title' => [Title::class, 'titles.show'],
    'event' => [Event::class, 'events.show'],
])->with([MembershipRole::Owner, MembershipRole::Member]);

test('a soft-deleted record has no show page', function (string $modelClass, string $routeName): void {
    // Arrange
    $record = $modelClass::factory()->create();
    $record->delete();
    actingAs(administrator());

    // Act
    $response = get(route($routeName, $record->getKey()));

    // Assert
    $response->assertNotFound();
})->with([
    'wrestler' => [Wrestler::class, 'wrestlers.show'],
    'manager' => [Manager::class, 'managers.show'],
    'referee' => [Referee::class, 'referees.show'],
    'tag team' => [TagTeam::class, 'tag-teams.show'],
    'stable' => [Stable::class, 'stables.show'],
    'title' => [Title::class, 'titles.show'],
]);
