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

use function Pest\Laravel\actingAs;

afterEach(function (): void {
    resolve(PromotionContextService::class)->clear();
});

/**
 * Bookable wrestlers inserted out of order: distinct names in reverse, then two namesakes with descending ids, so only
 * an ORDER BY on the name and then the id yields the returned ids.
 *
 * @return list<int> The wrestler ids in name, then id, order
 */
function bookableWrestlersOutOfOrder(): array
{
    $last = Wrestler::factory()->bookable()->create(['name' => 'Zack Ryder']);
    $first = Wrestler::factory()->bookable()->create(['name' => 'Adam Cole']);
    $secondNamesake = Wrestler::factory()->bookable()->create(['id' => 9002, 'name' => 'Matt Hardy']);
    $firstNamesake = Wrestler::factory()->bookable()->create(['id' => 9001, 'name' => 'Matt Hardy']);

    return [$first->id, $firstNamesake->id, $secondNamesake->id, $last->id];
}

describe('search', function (): void {
    it('offers at most twenty bookable records ordered by name when the term is empty', function (): void {
        // Arrange
        foreach (range(1, 25) as $number) {
            Wrestler::factory()->bookable()->create(['name' => sprintf('Wrestler %02d', $number)]);
        }

        // Act
        $options = resolve(BookableRosterSearch::class)->search(BookableRosterKind::Wrestlers, '', null);

        // Assert
        expect($options)->toHaveCount(20)
            ->and(array_column($options, 'name'))->toBe(array_map(
                fn (int $number): string => sprintf('Wrestler %02d', $number),
                range(1, 20),
            ));
    });

    it('orders matches by name and then by id whatever order they were added in', function (): void {
        // Arrange
        $expectedIds = bookableWrestlersOutOfOrder();

        // Act
        $options = resolve(BookableRosterSearch::class)->search(BookableRosterKind::Wrestlers, 'a', null);

        // Assert
        expect(array_column($options, 'id'))->toBe($expectedIds);
    });

    it('matches names case-insensitively anywhere in the name', function (): void {
        // Arrange
        Wrestler::factory()->bookable()->create(['name' => 'Ricky Steamboat']);
        Wrestler::factory()->bookable()->create(['name' => 'Randy Savage']);

        // Act
        $options = resolve(BookableRosterSearch::class)->search(BookableRosterKind::Wrestlers, '  STEAM ', null);

        // Assert
        expect(array_column($options, 'name'))->toBe(['Ricky Steamboat']);
    });

    it('only offers bookable records', function (string $unbookableState): void {
        // Arrange
        Wrestler::factory()->bookable()->create(['name' => 'Bookable Wrestler']);
        Wrestler::factory()->{$unbookableState}()->create(['name' => 'Unbookable Wrestler']);

        // Act
        $options = resolve(BookableRosterSearch::class)->search(BookableRosterKind::Wrestlers, 'Wrestler', null);

        // Assert
        expect(array_column($options, 'name'))->toBe(['Bookable Wrestler']);
    })->with(['unemployed', 'withFutureEmployment', 'released', 'retired', 'injured', 'suspended']);

    it('searches tag teams by team name and only offers bookable teams', function (): void {
        // Arrange
        TagTeam::factory()->bookable()->create(['name' => 'The Hart Foundation']);
        TagTeam::factory()->suspended()->create(['name' => 'The Hart Rivals']);
        TagTeam::factory()->bookable()->create(['name' => 'The Rockers']);

        // Act
        $options = resolve(BookableRosterSearch::class)->search(BookableRosterKind::TagTeams, 'hart', null);

        // Assert
        expect(array_column($options, 'name'))->toBe(['The Hart Foundation']);
    });

    it('searches referees by full name and only offers bookable referees', function (): void {
        // Arrange
        Referee::factory()->bookable()->create(['first_name' => 'Earl', 'last_name' => 'Hebner']);
        Referee::factory()->injured()->create(['first_name' => 'Earl', 'last_name' => 'Injured']);
        Referee::factory()->bookable()->create(['first_name' => 'Mike', 'last_name' => 'Chioda']);

        // Act
        $options = resolve(BookableRosterSearch::class)->search(BookableRosterKind::Referees, 'earl', null);

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
        $options = resolve(BookableRosterSearch::class)->search(BookableRosterKind::Wrestlers, 'Wrestler', $promotion->id);

        // Assert
        expect(array_column($options, 'name'))->toBe(['Our Wrestler']);
    });

    it('offers only the requested promotion roster to :dataset without a promotion context', function (Closure $actor, ?string $promotionName, array $expectedNames): void {
        // Arrange
        $actor();
        $promotions = [
            'Ours' => Promotion::factory()->create(),
            'Theirs' => Promotion::factory()->create(),
        ];
        Wrestler::factory()->for($promotions['Ours'], 'promotion')->bookable()->create(['name' => 'Our Wrestler']);
        Wrestler::factory()->for($promotions['Theirs'], 'promotion')->bookable()->create(['name' => 'Their Wrestler']);
        Wrestler::factory()->bookable()->create(['name' => 'Unowned Wrestler']);
        $promotionId = $promotionName === null ? null : $promotions[$promotionName]->id;

        // Act
        $options = resolve(BookableRosterSearch::class)->search(BookableRosterKind::Wrestlers, 'Wrestler', $promotionId);

        // Assert
        expect(array_column($options, 'name'))->toBe($expectedNames);
    })->with([
        'a global administrator' => [fn () => actingAs(administrator())],
        'the console' => [fn () => null],
    ])->with([
        'for a promotion' => ['Ours', ['Our Wrestler']],
        'for unowned records' => [null, ['Unowned Wrestler']],
    ]);

    it('treats wildcard and quote characters in the term as plain text', function (string $term): void {
        // Arrange
        Wrestler::factory()->bookable()->create(['name' => 'Ricky Steamboat']);
        Wrestler::factory()->bookable()->create(['name' => "Dan O'Brien"]);

        // Act
        $options = resolve(BookableRosterSearch::class)->search(BookableRosterKind::Wrestlers, $term, null);

        // Assert
        expect(array_column($options, 'name'))->toBeEmpty();
    })->with([
        'percent with text' => ['%zzz'],
        'underscore with text' => ['R_cky Zzz'],
        'backslash with text' => ['Ricky\\Zzz'],
        'quote injection' => ["' OR 1=1 --zzz"],
        'semicolon injection' => ['x"; DROP TABLE wrestlers; --zzz'],
    ]);

    it('drops wildcard characters from the term instead of letting them match any character', function (string $term): void {
        // Arrange
        Wrestler::factory()->bookable()->create(['name' => 'Ricky Steamboat']);

        // Act
        $options = resolve(BookableRosterSearch::class)->search(BookableRosterKind::Wrestlers, $term, null);

        // Assert
        expect($options)->toBeEmpty();
    })->with([
        'underscore standing in for one letter' => ['R_cky'],
        'percent standing in for one letter' => ['Ri%ky'],
        'percent standing in for several letters' => ['R%boat'],
    ]);

    it('still finds the name once the wildcard characters are dropped from the term', function (string $term): void {
        // Arrange
        Wrestler::factory()->bookable()->create(['name' => 'Ricky Steamboat']);

        // Act
        $options = resolve(BookableRosterSearch::class)->search(BookableRosterKind::Wrestlers, $term, null);

        // Assert
        expect(array_column($options, 'name'))->toBe(['Ricky Steamboat']);
    })->with([
        'stray underscore' => ['Ric_ky'],
        'stray percent' => ['Ric%ky'],
    ]);

    it('lists the first twenty records when the term only contains wildcards', function (): void {
        // Arrange
        Wrestler::factory()->bookable()->create(['name' => 'Ricky Steamboat']);

        // Act
        $options = resolve(BookableRosterSearch::class)->search(BookableRosterKind::Wrestlers, '%_', null);

        // Assert
        expect(array_column($options, 'name'))->toBe(['Ricky Steamboat']);
    });

    it('finds names containing an apostrophe', function (): void {
        // Arrange
        Wrestler::factory()->bookable()->create(['name' => "Dan O'Brien"]);

        // Act
        $options = resolve(BookableRosterSearch::class)->search(BookableRosterKind::Wrestlers, "O'Br", null);

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

    it('orders labels by name and then by id whatever order the ids were selected in', function (): void {
        // Arrange
        $expectedIds = bookableWrestlersOutOfOrder();

        // Act
        $labels = resolve(BookableRosterSearch::class)->labels(BookableRosterKind::Wrestlers, array_reverse($expectedIds));

        // Assert
        expect(array_column($labels, 'id'))->toBe($expectedIds);
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
