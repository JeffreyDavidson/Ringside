<?php

declare(strict_types=1);

use App\Enums\Roster\BookableRosterKind;
use App\Enums\Roster\RosterMemberKind;
use App\Livewire\Events\Components\TalentBookings;
use App\Livewire\Matches\Support\BookableRosterSearch;
use App\Livewire\Roster\Availability\Pages\Calendar;
use App\Livewire\Storylines\Components\Manage;
use App\Livewire\Storylines\Tables\Main as StorylinesMain;
use App\Livewire\Support\RosterMemberSearch;
use App\Models\Events\Event;
use App\Models\Promotions\Promotion;
use App\Models\Storylines\Storyline;
use App\Queries\Storylines\CreativeRosterQuery;
use App\Services\Promotions\PromotionContextService;

use function Pest\Laravel\actingAs;
use function Pest\Livewire\livewire;

afterEach(function (): void {
    resolve(PromotionContextService::class)->clear();
});

/**
 * Every place that searches by a typed term: how to get it ready, and how it runs one search.
 *
 * - prepare: signs in and builds what the search needs; returns the closure that runs one search with a term
 * - limit: the most characters of the term the search keeps, or null when it keeps them all
 *
 * @return array<string, array{prepare: Closure, limit: ?int}>
 */
function likeTermSearches(): array
{
    return [
        'creative roster query' => [
            'prepare' => function (): Closure {
                actingAs(administrator());
                $promotion = Promotion::factory()->create();

                return fn (string $term): array => resolve(CreativeRosterQuery::class)->get(BookableRosterKind::Wrestlers, $term, $promotion->id);
            },
            'limit' => null,
        ],
        'bookable roster search' => [
            'prepare' => function (): Closure {
                actingAs(administrator());
                $promotion = Promotion::factory()->create();

                return fn (string $term): array => resolve(BookableRosterSearch::class)->search(BookableRosterKind::Wrestlers, $term, $promotion->id);
            },
            'limit' => null,
        ],
        'roster member search' => [
            'prepare' => function (): Closure {
                actingAs(administrator());
                $promotion = Promotion::factory()->create();

                return fn (string $term): array => resolve(RosterMemberSearch::class)->search(RosterMemberKind::Wrestlers, $term, $promotion->id);
            },
            'limit' => null,
        ],
        'talent booking search' => [
            'prepare' => function (): Closure {
                actingAs(administrator());
                $event = Event::factory()->for(Promotion::factory()->create())->create();
                $component = livewire(TalentBookings::class, ['eventId' => $event->id]);

                return fn (string $term): mixed => $component->call('searchRoster', 'wrestlers', $term);
            },
            'limit' => 100,
        ],
        'availability calendar search' => [
            'prepare' => function (): Closure {
                actingAs(administrator());
                $promotion = Promotion::factory()->create();
                resolve(PromotionContextService::class)->set($promotion);
                resolve(PromotionContextService::class)->enforce();
                $component = livewire(Calendar::class)->set('date', '2026-10-17');

                return fn (string $term): mixed => $component->set('search', $term);
            },
            'limit' => null,
        ],
        'storyline title search' => [
            'prepare' => function (): Closure {
                actingAs(administrator());
                $component = livewire(StorylinesMain::class);

                return fn (string $term): mixed => $component->set('search', $term);
            },
            'limit' => null,
        ],
        'storyline participant search' => [
            'prepare' => function (): Closure {
                actingAs(administrator());
                $component = livewire(StorylinesMain::class);

                return fn (string $term): mixed => $component->set('participant', $term);
            },
            'limit' => null,
        ],
        'storyline event search' => [
            'prepare' => function (): Closure {
                actingAs(administrator());
                $story = Storyline::factory()->create();
                $component = livewire(Manage::class, ['storylineId' => $story->id])->call('begin', 'step');

                return fn (string $term): mixed => $component->set('eventSearch', $term);
            },
            'limit' => null,
        ],
    ];
}

dataset('like term searches', function (): Generator {
    $longTerm = str_repeat('x', 150);

    foreach (likeTermSearches() as $search => $spec) {
        $longPattern = '%'.str_repeat('x', $spec['limit'] ?? 150).'%';

        yield "{$search}, surrounding spaces" => [$spec['prepare'], '  Harbor Rival  ', ['%Harbor Rival%']];
        yield "{$search}, wildcards and backslash" => [$spec['prepare'], '100%_a\\b', ['%100ab%']];
        yield "{$search}, wildcards between words" => [$spec['prepare'], 'a % b', ['%a  b%']];
        yield "{$search}, only wildcards and spaces" => [$spec['prepare'], ' %_\\ ', []];
        yield "{$search}, a long term" => [$spec['prepare'], $longTerm, [$longPattern]];
    }
});

test('every search filters by the cleaned term, or not at all when nothing is left of it', function (Closure $prepare, string $term, array $patterns): void {
    // Arrange
    $search = $prepare();

    // Act
    $statements = recordStatements(fn () => $search($term));

    // Assert
    $likeBindings = collect($statements)
        ->flatMap(fn (array $statement): array => $statement['bindings'])
        ->filter(fn (mixed $binding): bool => is_string($binding) && str_starts_with($binding, '%') && str_ends_with($binding, '%'))
        ->unique()
        ->values()
        ->all();

    expect($likeBindings)->toBe($patterns);
})->with('like term searches');
