<?php

declare(strict_types=1);

use App\Exceptions\BaseBusinessException;
use App\Exceptions\Events\NameTakenException as EventNameTakenException;
use App\Exceptions\Roster\Individuals\CannotBeClearedFromInjuryException as IndividualCannotBeClearedFromInjuryException;
use App\Exceptions\Roster\Individuals\CannotBeEmployedException as IndividualCannotBeEmployedException;
use App\Exceptions\Roster\Individuals\CannotBeInjuredException as IndividualCannotBeInjuredException;
use App\Exceptions\Roster\Individuals\CannotBeReinstatedException as IndividualCannotBeReinstatedException;
use App\Exceptions\Roster\Individuals\CannotBeReleasedException as IndividualCannotBeReleasedException;
use App\Exceptions\Roster\Individuals\CannotBeRestoredException as IndividualCannotBeRestoredException;
use App\Exceptions\Roster\Individuals\CannotBeRetiredException as IndividualCannotBeRetiredException;
use App\Exceptions\Roster\Individuals\CannotBeSuspendedException as IndividualCannotBeSuspendedException;
use App\Exceptions\Roster\Individuals\CannotBeUnretiredException as IndividualCannotBeUnretiredException;
use App\Exceptions\Roster\Stables\CannotBeCreatedException;
use App\Exceptions\Roster\Stables\CannotBeDeletedException;
use App\Exceptions\Roster\Stables\CannotBeDisbandedException;
use App\Exceptions\Roster\Stables\CannotBeEstablishedException;
use App\Exceptions\Roster\Stables\CannotBeMergedException;
use App\Exceptions\Roster\Stables\CannotBeRestoredException;
use App\Exceptions\Roster\Stables\CannotBeRetiredException;
use App\Exceptions\Roster\Stables\CannotBeReunitedException;
use App\Exceptions\Roster\Stables\CannotBeSplitException;
use App\Exceptions\Roster\Stables\CannotBeUnretiredException;
use App\Exceptions\Roster\Stables\CannotBeUpdatedException;
use App\Exceptions\Roster\TagTeams\CannotBeEmployedException as TagTeamCannotBeEmployedException;
use App\Exceptions\Roster\TagTeams\CannotBeReinstatedException as TagTeamCannotBeReinstatedException;
use App\Exceptions\Roster\TagTeams\CannotBeReleasedException as TagTeamCannotBeReleasedException;
use App\Exceptions\Roster\TagTeams\CannotBeRestoredException as TagTeamCannotBeRestoredException;
use App\Exceptions\Roster\TagTeams\CannotBeRetiredException as TagTeamCannotBeRetiredException;
use App\Exceptions\Roster\TagTeams\CannotBeSuspendedException as TagTeamCannotBeSuspendedException;
use App\Exceptions\Roster\TagTeams\CannotBeUnretiredException as TagTeamCannotBeUnretiredException;
use App\Exceptions\Roster\TagTeams\NameTakenException as TagTeamNameTakenException;
use App\Exceptions\Roster\Wrestlers\NameTakenException as WrestlerNameTakenException;
use App\Exceptions\Titles\CannotBeDebutedException;
use App\Exceptions\Titles\CannotBePulledException;
use App\Exceptions\Titles\CannotBeReinstatedException;
use App\Exceptions\Titles\CannotBeRestoredException as TitleCannotBeRestoredException;
use App\Exceptions\Titles\CannotBeRetiredException as TitleCannotBeRetiredException;
use App\Exceptions\Titles\CannotBeUnretiredException as TitleCannotBeUnretiredException;
use App\Exceptions\Titles\CannotChangeTypeException;
use App\Exceptions\Titles\NameTakenException as TitleNameTakenException;
use App\Exceptions\Venues\NameTakenException as VenueNameTakenException;
use App\Models\Roster\Stables\Stable;
use App\Models\Roster\TagTeams\TagTeam;
use App\Models\Roster\Wrestlers\Wrestler;
use App\Models\Titles\Title;

dataset('translated exceptions', [
    CannotBeCreatedException::class,
    CannotBeDeletedException::class,
    CannotBeDisbandedException::class,
    CannotBeEstablishedException::class,
    CannotBeMergedException::class,
    CannotBeRestoredException::class,
    CannotBeRetiredException::class,
    CannotBeReunitedException::class,
    CannotBeSplitException::class,
    CannotBeUnretiredException::class,
    CannotBeUpdatedException::class,
    CannotBeDebutedException::class,
    CannotBePulledException::class,
    CannotBeReinstatedException::class,
    TitleCannotBeRestoredException::class,
    TitleCannotBeRetiredException::class,
    TitleCannotBeUnretiredException::class,
    CannotChangeTypeException::class,
    TagTeamNameTakenException::class,
    TitleNameTakenException::class,
    WrestlerNameTakenException::class,
    EventNameTakenException::class,
    VenueNameTakenException::class,
    IndividualCannotBeEmployedException::class,
    IndividualCannotBeReinstatedException::class,
    IndividualCannotBeReleasedException::class,
    IndividualCannotBeRestoredException::class,
    IndividualCannotBeRetiredException::class,
    IndividualCannotBeSuspendedException::class,
    IndividualCannotBeUnretiredException::class,
    IndividualCannotBeInjuredException::class,
    IndividualCannotBeClearedFromInjuryException::class,
    TagTeamCannotBeEmployedException::class,
    TagTeamCannotBeReinstatedException::class,
    TagTeamCannotBeReleasedException::class,
    TagTeamCannotBeRestoredException::class,
    TagTeamCannotBeRetiredException::class,
    TagTeamCannotBeSuspendedException::class,
    TagTeamCannotBeUnretiredException::class,
]);

it('resolves every factory message to translated text', function (string $exceptionClass): void {
    if (! is_subclass_of($exceptionClass, BaseBusinessException::class)) {
        throw new InvalidArgumentException("{$exceptionClass} is not a business exception.");
    }

    $arguments = [
        Stable::class => Stable::factory()->make(['name' => 'Evolution']),
        Title::class => Title::factory()->make(['name' => 'World']),
        Wrestler::class => Wrestler::factory()->make(['name' => 'Ric']),
        TagTeam::class => TagTeam::factory()->make(['name' => 'The Legacy']),
        'int' => 2,
        'string' => 'Ric',
        'array' => ['Ric', 'Dave'],
    ];

    $methods = new ReflectionClass($exceptionClass)->getMethods(ReflectionMethod::IS_STATIC | ReflectionMethod::IS_PUBLIC);

    foreach ($methods as $method) {
        if ($method->getDeclaringClass()->getName() !== $exceptionClass) {
            continue;
        }

        $parameters = array_map(
            fn (ReflectionParameter $parameter): mixed => $arguments[ltrim(explode('|', (string) $parameter->getType())[0], '?')],
            $method->getParameters(),
        );

        $exception = $method->invoke(null, ...$parameters);

        expect($exception)->toBeInstanceOf(BaseBusinessException::class);

        if ($exception instanceof BaseBusinessException) {
            expect($exception->getMessage())
                ->not->toStartWith('stables.')
                ->not->toStartWith('titles.')
                ->not->toMatch('/:[a-z_]+/')
                // A missing key makes __() return the key itself, such as "individuals.errors.retire.unemployed".
                ->not->toMatch('/^[\w-]+(\.[\w-]+)+$/');
        }
    }
})->with('translated exceptions');

it('pluralises the current member count when a stable cannot be deleted', function (int $count, string $expected): void {
    $stable = Stable::factory()->make(['name' => 'Evolution']);

    $message = CannotBeDeletedException::hasCurrentMembers($stable, $count)->getMessage();

    expect($message)->toBe($expected);
})->with([
    'one member' => [1, "Stable 'Evolution' has 1 current member and cannot be deleted. Remove members first or use disband action."],
    'several members' => [3, "Stable 'Evolution' has 3 current members and cannot be deleted. Remove members first or use disband action."],
]);

it('names the title when its type cannot be changed', function (): void {
    $title = Title::factory()->make(['name' => 'World']);

    $message = CannotChangeTypeException::hasChampionshipsOrMatches($title)->getMessage();

    expect($message)->toBe('Title [World] type cannot be changed because it has championship reigns or is booked in a match.');
});
