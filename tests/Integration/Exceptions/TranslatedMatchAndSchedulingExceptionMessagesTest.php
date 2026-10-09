<?php

declare(strict_types=1);

use App\Enums\MatchType;
use App\Enums\Titles\TitleType;
use App\Exceptions\BaseBusinessException;
use App\Exceptions\Events\CannotBeRestoredException as VenueCannotBeRestoredException;
use App\Exceptions\Lifecycle\InvalidDateRangeException;
use App\Exceptions\Matches\InvalidMatchConfigurationException;
use App\Exceptions\Matches\InvalidMatchOutcomeException;
use App\Exceptions\Scheduling\EntityNotAvailableException;
use App\Exceptions\Scheduling\SchedulingConflictException;
use App\Models\Events\Event;
use App\Models\Events\Venue;
use App\Models\Roster\TagTeams\TagTeam;
use App\Models\Roster\Wrestlers\Wrestler;
use App\Models\Titles\Title;
use Illuminate\Support\Carbon;

dataset('translated match and scheduling exceptions', [
    InvalidMatchConfigurationException::class,
    InvalidMatchOutcomeException::class,
    SchedulingConflictException::class,
    EntityNotAvailableException::class,
    InvalidDateRangeException::class,
    VenueCannotBeRestoredException::class,
]);

it('resolves every factory message to translated text', function (string $exceptionClass): void {
    if (! is_subclass_of($exceptionClass, BaseBusinessException::class)) {
        throw new InvalidArgumentException("{$exceptionClass} is not a business exception.");
    }

    $arguments = [
        MatchType::class => MatchType::Singles,
        TitleType::class => TitleType::Singles,
        Title::class => Title::factory()->make(['name' => 'World']),
        Event::class => Event::factory()->make(['name' => 'Summer Slam']),
        Venue::class => Venue::factory()->make(['name' => 'Garden']),
        Wrestler::class => Wrestler::factory()->make(['name' => 'Ric']),
        TagTeam::class => TagTeam::factory()->make(['name' => 'Dudleys']),
        Carbon::class => Carbon::parse('2026-01-01'),
        'int' => 2,
        'string' => 'Ric',
        'array' => [1, 1],
    ];

    $methods = new ReflectionClass($exceptionClass)->getMethods(ReflectionMethod::IS_STATIC | ReflectionMethod::IS_PUBLIC);

    foreach ($methods as $method) {
        if ($method->getDeclaringClass()->getName() !== $exceptionClass) {
            continue;
        }

        foreach ([false, true] as $useNulls) {
            $parameters = array_map(
                function (ReflectionParameter $parameter) use ($arguments, $useNulls): mixed {
                    if ($useNulls && $parameter->allowsNull()) {
                        return null;
                    }

                    $name = ltrim(explode('|', (string) $parameter->getType())[0], '?');

                    return $arguments[$name];
                },
                $method->getParameters(),
            );

            $exception = $method->invoke(null, ...$parameters);

            expect($exception)->toBeInstanceOf(BaseBusinessException::class);

            if ($exception instanceof BaseBusinessException) {
                expect($exception->getMessage())
                    ->not->toStartWith('matches.')
                    ->not->toStartWith('core.')
                    ->not->toStartWith('venues.')
                    ->not->toStartWith('events.')
                    ->not->toMatch('/:[a-z_]+/');
            }
        }
    }
})->with('translated match and scheduling exceptions');

it('renders the original English text', function (Closure $factory, string $expected): void {
    expect($factory()->getMessage())->toBe($expected);
})->with([
    'side count' => [fn () => InvalidMatchConfigurationException::incorrectSideCount(2), 'This match requires exactly 2 competitor sides.'],
    'competitor range' => [fn () => InvalidMatchConfigurationException::invalidCompetitorCount(2, 4), 'This match requires between 2 and 4 competitors.'],
    'competitor minimum' => [fn () => InvalidMatchConfigurationException::invalidCompetitorCount(3, null), 'This match requires at least 3 competitors.'],
    'side composition' => [fn () => InvalidMatchConfigurationException::invalidSideComposition(MatchType::Singles, [1, 1]), 'The [Singles] match requires a 1-on-1 roster-member composition.'],
    'promotion' => [fn () => InvalidMatchConfigurationException::outsideEventPromotion('wrestlers'), "Selected wrestlers must all belong to the event's promotion."],
    'title winner' => [fn () => InvalidMatchOutcomeException::invalidTitleWinner(TitleType::Singles), 'The winning side must contain exactly one singles championship competitor.'],
    'competitor booked' => [fn () => SchedulingConflictException::competitorAlreadyBooked('Wrestler', 'Ric'), 'Wrestler [Ric] is already booked at this event time.'],
    'venue booked' => [fn () => SchedulingConflictException::venueAlreadyBooked('Garden', 'Jan 1, 2026'), 'Venue [Garden] is already booked on Jan 1, 2026 (venue time).'],
    'not available' => [fn () => EntityNotAvailableException::forMatchAssignment('wrestlers'), 'Selected wrestlers must all be eligible for match assignment.'],
    'range' => [fn () => InvalidDateRangeException::endBeforeStart(Carbon::parse('2026-02-01'), Carbon::parse('2026-01-01')), 'Invalid date range: end date (2026-01-01) cannot be before start date (2026-02-01). Ensure logical date ordering.'],
    'range with context' => [fn () => InvalidDateRangeException::endBeforeStart(Carbon::parse('2026-02-01'), Carbon::parse('2026-01-01'), 'stable'), 'Invalid date range for stable: end date (2026-01-01) cannot be before start date (2026-02-01). Ensure logical date ordering.'],
    'future' => [fn () => InvalidDateRangeException::futureNotAllowed(Carbon::parse('2026-02-01'), 'Debut'), 'Debut date (2026-02-01) cannot be in the future. Use current or past date only.'],
    'venue name conflict' => [fn () => VenueCannotBeRestoredException::nameConflict(Venue::factory()->make(['name' => 'Garden']), 'Arena'), "Venue 'Garden' cannot be restored because the name conflicts with existing venue 'Arena'. Resolve the conflict before restoration."],
    'event venue deleted' => [fn () => VenueCannotBeRestoredException::venueDeleted(Event::factory()->make(['name' => 'Summer Slam']), Venue::factory()->make(['name' => 'Garden'])), "Event 'Summer Slam' cannot be restored because its venue 'Garden' is deleted. Restore the venue first."],
    'venue double booked' => [fn () => VenueCannotBeRestoredException::venueDoubleBooked(Venue::factory()->make(['name' => 'Garden']), 'Jan 1, 2026'), "Venue 'Garden' cannot be restored because it hosts more than one event on Jan 1, 2026. Move or delete the extra events first."],
]);
