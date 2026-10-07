<?php

declare(strict_types=1);

use App\Actions\Managers\DeleteAction as ManagerDeleteAction;
use App\Actions\Managers\RestoreAction as ManagerRestoreAction;
use App\Actions\Referees\DeleteAction as RefereeDeleteAction;
use App\Actions\Referees\RestoreAction as RefereeRestoreAction;
use App\Actions\TagTeams\DeleteAction as TagTeamDeleteAction;
use App\Actions\TagTeams\RestoreAction as TagTeamRestoreAction;
use App\Actions\Wrestlers\DeleteAction as WrestlerDeleteAction;
use App\Actions\Wrestlers\RestoreAction as WrestlerRestoreAction;
use App\Models\Roster\Managers\Manager;
use App\Models\Roster\Referees\Referee;
use App\Models\Roster\TagTeams\TagTeam;
use App\Models\Roster\Wrestlers\Wrestler;
use Illuminate\Support\Carbon;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\travelTo;

beforeEach(function (): void {
    travelTo(Carbon::parse('2026-03-10 09:00:00'));
});

describe('restoring a record that was deleted with a scheduled employment', function (): void {
    test('it closes the scheduled employment on its own start date and restores the record', function (
        string $modelClass,
        string $deleteActionClass,
        string $restoreActionClass,
    ): void {
        // Arrange
        $startedAt = Carbon::parse('2026-03-11 00:00:00');
        $subject = $modelClass::factory()->create();
        $employment = $subject->employments()->create(['started_at' => $startedAt]);

        actingAs(administrator());
        resolve($deleteActionClass)->handle($subject);

        // Act
        resolve($restoreActionClass)->handle($subject->refresh());

        // Assert
        expect($subject->refresh()->trashed())->toBeFalse()
            ->and($employment->refresh()->ended_at?->toDateTimeString())->toBe($startedAt->toDateTimeString())
            ->and($subject->employments()->whereNull('ended_at')->exists())->toBeFalse();
    })->with([
        'wrestler' => [Wrestler::class, WrestlerDeleteAction::class, WrestlerRestoreAction::class],
        'manager' => [Manager::class, ManagerDeleteAction::class, ManagerRestoreAction::class],
        'referee' => [Referee::class, RefereeDeleteAction::class, RefereeRestoreAction::class],
    ]);

    test('it restores a tag team deleted with a scheduled employment', function (): void {
        // Arrange
        $startedAt = Carbon::parse('2026-03-11 00:00:00');
        $tagTeam = TagTeam::factory()->create();
        $employment = $tagTeam->employments()->create(['started_at' => $startedAt]);

        actingAs(administrator());
        resolve(TagTeamDeleteAction::class)->handle($tagTeam);

        // Act
        resolve(TagTeamRestoreAction::class)->handle($tagTeam->refresh());

        // Assert
        expect($tagTeam->refresh()->trashed())->toBeFalse()
            ->and($employment->refresh()->ended_at?->equalTo($startedAt))->toBeTrue();
    });

    test('it closes a scheduled employment left open by an earlier deletion on its own start date', function (): void {
        // Arrange
        $startedAt = Carbon::parse('2026-03-11 00:00:00');
        $manager = Manager::factory()->create();
        $employment = $manager->employments()->create(['started_at' => $startedAt]);
        $manager->delete();

        actingAs(administrator());

        // Act
        resolve(ManagerRestoreAction::class)->handle($manager->refresh());

        // Assert
        expect($manager->refresh()->trashed())->toBeFalse()
            ->and($employment->refresh()->ended_at?->toDateTimeString())->toBe($startedAt->toDateTimeString());
    });

    test('it ends an employment that has already started on the restore date', function (): void {
        // Arrange
        $manager = Manager::factory()->create();
        $employment = $manager->employments()->create(['started_at' => Carbon::parse('2026-03-01 00:00:00')]);
        $manager->delete();

        actingAs(administrator());

        // Act
        resolve(ManagerRestoreAction::class)->handle($manager->refresh());

        // Assert
        expect($employment->refresh()->ended_at?->toDateTimeString())->toBe('2026-03-10 09:00:00');
    });
});
