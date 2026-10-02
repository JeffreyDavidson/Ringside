<?php

declare(strict_types=1);

use App\Enums\Roster\BookableRosterKind;
use App\Livewire\Matches\Support\BookableRosterSearch;
use App\Models\Promotions\Promotion;
use App\Models\Roster\Referees\Referee;
use App\Models\Roster\TagTeams\TagTeam;
use App\Models\Roster\Wrestlers\Wrestler;
use App\Services\Promotions\PromotionContextService;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Support\Facades\DB;

afterEach(function (): void {
    resolve(PromotionContextService::class)->clear();
});

describe('search', function (): void {
    it('offers at most twenty bookable records ordered by name when the term is empty', function (): void {
        // Arrange
        foreach (range(1, 25) as $number) {
            Wrestler::factory()->bookable()->create(['name' => sprintf('Wrestler %02d', $number)]);
        }

        // Act
        $options = resolve(BookableRosterSearch::class)->search(BookableRosterKind::Wrestlers, '');

        // Assert
        expect($options)->toHaveCount(20)
            ->and(array_column($options, 'name'))->toBe(array_map(
                fn (int $number): string => sprintf('Wrestler %02d', $number),
                range(1, 20),
            ));
    });

    it('matches names case-insensitively anywhere in the name', function (): void {
        // Arrange
        Wrestler::factory()->bookable()->create(['name' => 'Ricky Steamboat']);
        Wrestler::factory()->bookable()->create(['name' => 'Randy Savage']);

        // Act
        $options = resolve(BookableRosterSearch::class)->search(BookableRosterKind::Wrestlers, '  STEAM ');

        // Assert
        expect(array_column($options, 'name'))->toBe(['Ricky Steamboat']);
    });

    it('only offers bookable records', function (string $unbookableState): void {
        // Arrange
        Wrestler::factory()->bookable()->create(['name' => 'Bookable Wrestler']);
        Wrestler::factory()->{$unbookableState}()->create(['name' => 'Unbookable Wrestler']);

        // Act
        $options = resolve(BookableRosterSearch::class)->search(BookableRosterKind::Wrestlers, 'Wrestler');

        // Assert
        expect(array_column($options, 'name'))->toBe(['Bookable Wrestler']);
    })->with(['unemployed', 'withFutureEmployment', 'released', 'retired', 'injured', 'suspended']);

    it('searches tag teams by team name and only offers bookable teams', function (): void {
        // Arrange
        TagTeam::factory()->bookable()->create(['name' => 'The Hart Foundation']);
        TagTeam::factory()->suspended()->create(['name' => 'The Hart Rivals']);
        TagTeam::factory()->bookable()->create(['name' => 'The Rockers']);

        // Act
        $options = resolve(BookableRosterSearch::class)->search(BookableRosterKind::TagTeams, 'hart');

        // Assert
        expect(array_column($options, 'name'))->toBe(['The Hart Foundation']);
    });

    it('searches referees by full name and only offers bookable referees', function (): void {
        // Arrange
        Referee::factory()->bookable()->create(['first_name' => 'Earl', 'last_name' => 'Hebner']);
        Referee::factory()->injured()->create(['first_name' => 'Earl', 'last_name' => 'Injured']);
        Referee::factory()->bookable()->create(['first_name' => 'Mike', 'last_name' => 'Chioda']);

        // Act
        $options = resolve(BookableRosterSearch::class)->search(BookableRosterKind::Referees, 'earl');

        // Assert
        expect(array_column($options, 'name'))->toBe(['Earl Hebner']);
    });

    it('never offers another promotions roster', function (): void {
        // Arrange
        [$promotion, $otherPromotion] = Promotion::factory()->count(2)->create()->all();
        Wrestler::factory()->for($promotion, 'promotion')->bookable()->create(['name' => 'Our Wrestler']);
        Wrestler::factory()->for($otherPromotion, 'promotion')->bookable()->create(['name' => 'Their Wrestler']);
        $context = resolve(PromotionContextService::class);
        $context->set($promotion);
        $context->enforce();

        // Act
        $options = resolve(BookableRosterSearch::class)->search(BookableRosterKind::Wrestlers, 'Wrestler');

        // Assert
        expect(array_column($options, 'name'))->toBe(['Our Wrestler']);
    });

    it('treats wildcard and quote characters in the term as plain text', function (string $term): void {
        // Arrange
        Wrestler::factory()->bookable()->create(['name' => 'Ricky Steamboat']);
        Wrestler::factory()->bookable()->create(['name' => "Dan O'Brien"]);

        // Act
        $options = resolve(BookableRosterSearch::class)->search(BookableRosterKind::Wrestlers, $term);

        // Assert
        expect(array_column($options, 'name'))->toBeEmpty();
    })->with([
        'percent with text' => ['%zzz'],
        'underscore with text' => ['R_cky Zzz'],
        'backslash with text' => ['Ricky\\Zzz'],
        'quote injection' => ["' OR 1=1 --zzz"],
        'semicolon injection' => ['x"; DROP TABLE wrestlers; --zzz'],
    ]);

    it('lists the first twenty records when the term only contains wildcards', function (): void {
        // Arrange
        Wrestler::factory()->bookable()->create(['name' => 'Ricky Steamboat']);

        // Act
        $options = resolve(BookableRosterSearch::class)->search(BookableRosterKind::Wrestlers, '%_');

        // Assert
        expect(array_column($options, 'name'))->toBe(['Ricky Steamboat']);
    });

    it('finds names containing an apostrophe', function (): void {
        // Arrange
        Wrestler::factory()->bookable()->create(['name' => "Dan O'Brien"]);

        // Act
        $options = resolve(BookableRosterSearch::class)->search(BookableRosterKind::Wrestlers, "O'Br");

        // Assert
        expect(array_column($options, 'name'))->toBe(["Dan O'Brien"]);
    });
});

describe('labels', function (): void {
    it('resolves names for selected ids even when no longer bookable or trashed', function (): void {
        // Arrange
        $bookable = Wrestler::factory()->bookable()->create(['name' => 'Bookable Wrestler']);
        $retired = Wrestler::factory()->retired()->create(['name' => 'Retired Wrestler']);
        $trashed = Wrestler::factory()->bookable()->create(['name' => 'Trashed Wrestler']);
        $trashed->delete();
        $unselected = Wrestler::factory()->bookable()->create(['name' => 'Unselected Wrestler']);

        // Act
        $labels = resolve(BookableRosterSearch::class)->labels(
            BookableRosterKind::Wrestlers,
            [$bookable->id, $retired->id, $trashed->id, 'not-an-id', null, (string) $bookable->id],
        );

        // Assert
        expect(array_column($labels, 'name'))->toBe(['Bookable Wrestler', 'Retired Wrestler', 'Trashed Wrestler'])
            ->and(array_column($labels, 'id'))->not->toContain($unselected->id);
    });

    it('resolves tag team and referee names', function (): void {
        // Arrange
        $tagTeam = TagTeam::factory()->suspended()->create(['name' => 'Suspended Team']);
        $referee = Referee::factory()->retired()->create(['first_name' => 'Earl', 'last_name' => 'Hebner']);
        $search = resolve(BookableRosterSearch::class);

        // Act
        $tagTeamLabels = $search->labels(BookableRosterKind::TagTeams, [$tagTeam->id]);
        $refereeLabels = $search->labels(BookableRosterKind::Referees, [$referee->id]);

        // Assert
        expect($tagTeamLabels)->toBe([['id' => $tagTeam->id, 'name' => 'Suspended Team']])
            ->and($refereeLabels)->toBe([['id' => $referee->id, 'name' => 'Earl Hebner']]);
    });

    it('does not query when there is nothing selected', function (): void {
        // Arrange
        $search = resolve(BookableRosterSearch::class);

        // Act
        $queries = [];
        DB::listen(function (QueryExecuted $query) use (&$queries): void {
            $queries[] = $query->sql;
        });
        $labels = $search->labels(BookableRosterKind::Wrestlers, [null, '']);

        // Assert
        expect($labels)->toBeEmpty()
            ->and($queries)->toBeEmpty();
    });
});
