<?php

declare(strict_types=1);

namespace App\Livewire\Support;

use App\Enums\Roster\RosterEntityType;
use App\Exceptions\BaseBusinessException;
use App\Exceptions\Roster\Individuals\CannotBeClearedFromInjuryException;
use App\Exceptions\Roster\Individuals\CannotBeEmployedException;
use App\Exceptions\Roster\Individuals\CannotBeInjuredException;
use App\Exceptions\Roster\Individuals\CannotBeReinstatedException;
use App\Exceptions\Roster\Individuals\CannotBeReleasedException;
use App\Exceptions\Roster\Individuals\CannotBeRestoredException;
use App\Exceptions\Roster\Individuals\CannotBeRetiredException;
use App\Exceptions\Roster\Individuals\CannotBeSuspendedException;
use App\Exceptions\Roster\Individuals\CannotBeUnretiredException;
use App\Exceptions\Roster\TagTeams\CannotBeEmployedException as TagTeamCannotBeEmployedException;
use App\Exceptions\Roster\TagTeams\CannotBeReinstatedException as TagTeamCannotBeReinstatedException;
use App\Exceptions\Roster\TagTeams\CannotBeReleasedException as TagTeamCannotBeReleasedException;
use App\Exceptions\Roster\TagTeams\CannotBeRestoredException as TagTeamCannotBeRestoredException;
use App\Exceptions\Roster\TagTeams\CannotBeRetiredException as TagTeamCannotBeRetiredException;
use App\Exceptions\Roster\TagTeams\CannotBeSuspendedException as TagTeamCannotBeSuspendedException;
use App\Exceptions\Roster\TagTeams\CannotBeUnretiredException as TagTeamCannotBeUnretiredException;
use Illuminate\Support\Facades\Lang;

final class RosterErrorMessageResolver
{
    public static function translationKey(BaseBusinessException $exception, RosterEntityType $entityType): string
    {
        $namespace = "{$entityType->translationNamespace()}.errors";

        $action = match ($exception::class) {
            CannotBeEmployedException::class,
            TagTeamCannotBeEmployedException::class => 'employ',
            CannotBeReleasedException::class,
            TagTeamCannotBeReleasedException::class => 'release',
            CannotBeRetiredException::class,
            TagTeamCannotBeRetiredException::class => 'retire',
            CannotBeUnretiredException::class,
            TagTeamCannotBeUnretiredException::class => 'unretire',
            CannotBeSuspendedException::class,
            TagTeamCannotBeSuspendedException::class => 'suspend',
            CannotBeReinstatedException::class,
            TagTeamCannotBeReinstatedException::class => 'reinstate',
            CannotBeInjuredException::class => 'injure',
            CannotBeClearedFromInjuryException::class => 'clear_from_injury',
            CannotBeRestoredException::class,
            TagTeamCannotBeRestoredException::class => 'restore',
            default => null,
        };

        if ($action === null) {
            return "{$namespace}.general";
        }

        $reasonKey = "{$namespace}.{$action}.{$exception->reason()->value}";

        return Lang::has($reasonKey)
            ? $reasonKey
            : "{$namespace}.{$action}.default";
    }
}
