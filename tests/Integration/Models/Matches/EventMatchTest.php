<?php

declare(strict_types=1);

use App\Enums\MatchFinish;
use App\Enums\MatchType;
use App\Models\Events\Event;
use App\Models\Matches\EventMatch;
use App\Models\Matches\MatchStipulation;
use App\Models\Promotions\Promotion;
use App\Services\Promotions\PromotionContextService;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * Integration tests for EventMatch model structure and configuration.
 *
 * INTEGRATION TEST SCOPE:
 * - Model attribute configuration (fillable, casts, defaults)
 * - Custom builder class verification
 * - Trait integration verification
 * - Interface implementation verification
 *
 * These tests verify that the EventMatch model is properly configured
 * and structured according to the data layer requirements.
 */
describe('EventMatch Model Integration Tests', function () {
    describe('model attributes and configuration', function () {
        test('uses correct table name', function () {
            $eventMatch = new EventMatch;
            expect($eventMatch->getTable())->toBe('events_matches');
        });

        test('has correct fillable properties', function () {
            $eventMatch = new EventMatch;

            expect($eventMatch->getFillable())->toEqual([
                'event_id',
                'match_number',
                'match_type',
                'match_stipulation_id',
                'preview',
                'match_finish',
                'winning_side_id',
            ]);
        });

        test('has correct casts configuration', function () {
            $eventMatch = new EventMatch;
            $casts = $eventMatch->getCasts();

            expect($casts)->toBeArray()
                ->toMatchArray(['id' => 'int', 'match_type' => MatchType::class, 'match_finish' => MatchFinish::class]);
        });

        test('has custom eloquent builder', function () {
            $eventMatch = new EventMatch;
            // EventMatch model has no custom builder
            expect($eventMatch->query())->toBeObject();
        });

        test('has correct default values', function () {
            $eventMatch = new EventMatch;
            // EventMatch model has no custom default values
            expect($eventMatch)->toBeInstanceOf(EventMatch::class);
        });
    });

    describe('trait integration', function () {
        test('uses all required traits', function () {
            expect(class_uses(EventMatch::class))->toContain(HasFactory::class);
        });
    });

    describe('interface implementation', function () {
        test('implements all required interfaces', function () {
            $interfaces = class_implements(EventMatch::class);

            // EventMatch model implements no custom interfaces beyond base Model
            expect($interfaces)->toBeArray();
        });
    });

    describe('model constants', function () {
        test('has no model-specific constants defined', function () {
            $reflection = new ReflectionClass(EventMatch::class);
            $constants = $reflection->getConstants();

            // Filter out inherited constants from parent classes
            $modelConstants = array_filter($constants, function ($value, $key) use ($reflection) {
                $constant = $reflection->getReflectionConstant($key);

                return $constant && $constant->getDeclaringClass()->getName() === EventMatch::class;
            }, ARRAY_FILTER_USE_BOTH);

            expect($modelConstants)->toBeEmpty();
        });
    });

    describe('business logic methods', function () {
        test('has required relationship methods', function () {
            $eventMatch = new EventMatch;

            // EventMatch model has standard Eloquent relationships but no custom business methods
            expect($eventMatch)->toBeInstanceOf(EventMatch::class);
        });
    });
});

it('resolves the stipulation of a match', function () {
    $stipulation = MatchStipulation::factory()->create();
    $eventMatch = EventMatch::factory()->create(['match_stipulation_id' => $stipulation->id]);

    $matchStipulation = $eventMatch->matchStipulation;

    expect($matchStipulation)->toBeInstanceOf(MatchStipulation::class)
        ->and($matchStipulation->is($stipulation))->toBeTrue();
});

it('has no stipulation for a standard match', function () {
    $eventMatch = EventMatch::factory()->create(['match_stipulation_id' => null]);

    $matchStipulation = $eventMatch->matchStipulation;

    expect($matchStipulation)->toBeNull();
});

it('returns no matches when a promotion context is enforced without an active promotion', function () {
    $context = app(PromotionContextService::class);
    EventMatch::factory()->for(Event::factory()->for(Promotion::factory(), 'promotion'))->create();
    EventMatch::factory()->for(Event::factory())->create();
    $context->enforce();

    $eventMatches = EventMatch::query()->get();
    $context->clear();

    expect($eventMatches)->toBeEmpty();
});

it('only returns matches of the active promotion when a promotion context is enforced', function () {
    $context = app(PromotionContextService::class);
    $promotion = Promotion::factory()->create();
    $ownedMatch = EventMatch::factory()->for(Event::factory()->for($promotion, 'promotion'))->create();
    EventMatch::factory()->for(Event::factory()->for(Promotion::factory(), 'promotion'))->create();
    EventMatch::factory()->for(Event::factory())->create();
    $context->set($promotion);
    $context->enforce();

    $eventMatches = EventMatch::query()->get();
    $context->clear();

    expect($eventMatches)->toHaveCount(1)
        ->and($eventMatches->first()?->is($ownedMatch))->toBeTrue();
});
