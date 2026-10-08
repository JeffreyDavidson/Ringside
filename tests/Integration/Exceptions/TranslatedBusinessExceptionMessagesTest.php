<?php

declare(strict_types=1);

use App\Exceptions\BaseBusinessException;
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
use App\Exceptions\Titles\CannotBeDebutedException;
use App\Exceptions\Titles\CannotBePulledException;
use App\Exceptions\Titles\CannotBeReinstatedException;
use App\Exceptions\Titles\CannotBeRestoredException as TitleCannotBeRestoredException;
use App\Exceptions\Titles\CannotBeRetiredException as TitleCannotBeRetiredException;
use App\Exceptions\Titles\CannotBeUnretiredException as TitleCannotBeUnretiredException;
use App\Exceptions\Titles\CannotChangeTypeException;
use App\Models\Roster\Stables\Stable;
use App\Models\Titles\Title;

dataset('translated exceptions', [
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
]);

it('resolves every factory message to translated text', function (string $exceptionClass): void {
    if (! is_subclass_of($exceptionClass, BaseBusinessException::class)) {
        throw new InvalidArgumentException("{$exceptionClass} is not a business exception.");
    }

    $arguments = [
        Stable::class => Stable::factory()->make(['name' => 'Evolution']),
        Title::class => Title::factory()->make(['name' => 'World']),
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
            fn (ReflectionParameter $parameter): mixed => $arguments[(string) $parameter->getType()],
            $method->getParameters(),
        );

        $exception = $method->invoke(null, ...$parameters);

        expect($exception)->toBeInstanceOf(BaseBusinessException::class);

        if ($exception instanceof BaseBusinessException) {
            expect($exception->getMessage())
                ->not->toStartWith('stables.')
                ->not->toStartWith('titles.')
                ->not->toMatch('/:[a-z_]+/');
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
