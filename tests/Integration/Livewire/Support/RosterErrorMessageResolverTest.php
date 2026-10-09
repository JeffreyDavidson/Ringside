<?php

declare(strict_types=1);

use App\Enums\BusinessRuleReason;
use App\Enums\Roster\RosterEntityType;
use App\Enums\Roster\RosterLifecycleAction;
use App\Exceptions\BaseBusinessException;
use App\Exceptions\Roster\Individuals\CannotBeClearedFromInjuryException;
use App\Exceptions\Roster\Individuals\CannotBeDeletedException;
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
use App\Livewire\Support\RosterErrorMessageResolver;
use App\Models\Roster\Managers\Manager;
use App\Models\Roster\Referees\Referee;
use App\Models\Roster\TagTeams\TagTeam;
use App\Models\Roster\Wrestlers\Wrestler;
use Illuminate\Support\Arr;

$individualTypes = [RosterEntityType::Wrestler, RosterEntityType::Manager, RosterEntityType::Referee];

$newIndividual = fn (RosterEntityType $type): Wrestler|Manager|Referee => match ($type) {
    RosterEntityType::Manager => new Manager(['name' => 'Test Manager']),
    RosterEntityType::Referee => new Referee(['name' => 'Test Referee']),
    default => new Wrestler(['name' => 'Test Wrestler']),
};

// Every failure the roster lifecycle can raise, and the translation it must resolve to. A failure raised without a
// stable reason (for example a future employment) is expected to use the action's default message.
$individualFailures = [
    'employ already_employed' => [CannotBeEmployedException::employed(...), 'employ.already_employed'],
    'employ retired' => [CannotBeEmployedException::retired(...), 'employ.retired'],
    'employ future employment' => [CannotBeEmployedException::hasFutureEmployment(...), 'employ.default'],
    'release unemployed' => [CannotBeReleasedException::unemployed(...), 'release.unemployed'],
    'retire unemployed' => [CannotBeRetiredException::unemployed(...), 'retire.unemployed'],
    'retire already_retired' => [CannotBeRetiredException::alreadyRetired(...), 'retire.already_retired'],
    'unretire not_retired' => [CannotBeUnretiredException::notRetired(...), 'unretire.not_retired'],
    'unretire deleted' => [CannotBeUnretiredException::deleted(...), 'unretire.default'],
    'suspend unemployed' => [CannotBeSuspendedException::unemployed(...), 'suspend.unemployed'],
    'suspend already_suspended' => [CannotBeSuspendedException::suspended(...), 'suspend.already_suspended'],
    'suspend injured' => [CannotBeSuspendedException::injured(...), 'suspend.injured'],
    'suspend retired' => [CannotBeSuspendedException::retired(...), 'suspend.default'],
    'reinstate injured' => [CannotBeReinstatedException::injured(...), 'reinstate.injured'],
    'reinstate not_suspended' => [CannotBeReinstatedException::available(...), 'reinstate.not_suspended'],
    'injure unemployed' => [CannotBeInjuredException::unemployed(...), 'injure.unemployed'],
    'injure already_injured' => [CannotBeInjuredException::injured(...), 'injure.already_injured'],
    'injure suspended' => [CannotBeInjuredException::suspended(...), 'injure.suspended'],
    'clear_from_injury not_injured' => [CannotBeClearedFromInjuryException::notInjured(...), 'clear_from_injury.not_injured'],
    'restore not_deleted' => [CannotBeRestoredException::notDeleted(...), 'restore.not_deleted'],
];

$tagTeamFailures = [
    'employ already_employed' => [TagTeamCannotBeEmployedException::alreadyEmployed(...), 'employ.already_employed'],
    'employ retired' => [TagTeamCannotBeEmployedException::retired(...), 'employ.retired'],
    'employ future employment' => [TagTeamCannotBeEmployedException::hasFutureEmployment(...), 'employ.default'],
    'release unemployed' => [TagTeamCannotBeReleasedException::notEmployed(...), 'release.unemployed'],
    'retire unemployed' => [TagTeamCannotBeRetiredException::notEmployed(...), 'retire.unemployed'],
    'retire already_retired' => [TagTeamCannotBeRetiredException::alreadyRetired(...), 'retire.already_retired'],
    'unretire not_retired' => [TagTeamCannotBeUnretiredException::notRetired(...), 'unretire.not_retired'],
    'unretire name conflict' => [fn (TagTeam $member): BaseBusinessException => TagTeamCannotBeUnretiredException::nameConflict($member, 'Other Team'), 'unretire.default'],
    'unretire partner deleted' => [fn (TagTeam $member): BaseBusinessException => TagTeamCannotBeUnretiredException::partnerDeleted($member, 'Some Wrestler'), 'unretire.default'],
    'unretire partner on another tag team' => [fn (TagTeam $member): BaseBusinessException => TagTeamCannotBeUnretiredException::partnerOnAnotherTagTeam($member, 'Some Wrestler', 'Other Team'), 'unretire.default'],
    'suspend unemployed' => [TagTeamCannotBeSuspendedException::notEmployed(...), 'suspend.unemployed'],
    'suspend already_suspended' => [TagTeamCannotBeSuspendedException::alreadySuspended(...), 'suspend.already_suspended'],
    'reinstate not_suspended' => [TagTeamCannotBeReinstatedException::notSuspended(...), 'reinstate.not_suspended'],
    'restore not_deleted' => [TagTeamCannotBeRestoredException::notDeleted(...), 'restore.not_deleted'],
    'restore name conflict' => [fn (TagTeam $member): BaseBusinessException => TagTeamCannotBeRestoredException::nameConflict($member, 'Other Team'), 'restore.default'],
];

$rosterFailures = [];
$reachableKeysByEntity = [];

foreach ($individualTypes as $type) {
    foreach ($individualFailures as $label => [$makeFailure, $key]) {
        $rosterFailures["{$type->value} {$label}"] = [
            fn (): BaseBusinessException => $makeFailure($newIndividual($type)),
            $type,
            "{$type->translationNamespace()}.errors.{$key}",
        ];
        $reachableKeysByEntity[$type->value][] = "{$type->translationNamespace()}.errors.{$key}";
    }
}

foreach ($tagTeamFailures as $label => [$makeFailure, $key]) {
    $rosterFailures["tag-team {$label}"] = [
        fn (): BaseBusinessException => $makeFailure(new TagTeam(['name' => 'Test Team'])),
        RosterEntityType::TagTeam,
        "tag-teams.errors.{$key}",
    ];
    $reachableKeysByEntity['tag-team'][] = "tag-teams.errors.{$key}";
}

dataset('roster lifecycle failures', $rosterFailures);

dataset('roster entities with reachable translation keys', fn (): array => collect(RosterEntityType::cases())
    ->mapWithKeys(fn (RosterEntityType $type): array => [
        $type->value => [$type, array_values(array_unique($reachableKeysByEntity[$type->value]))],
    ])
    ->all());

dataset('mapped roster exceptions', [
    'individual employ' => [CannotBeEmployedException::class, false],
    'individual release' => [CannotBeReleasedException::class, false],
    'individual retire' => [CannotBeRetiredException::class, false],
    'individual unretire' => [CannotBeUnretiredException::class, false],
    'individual suspend' => [CannotBeSuspendedException::class, false],
    'individual reinstate' => [CannotBeReinstatedException::class, false],
    'individual injure' => [CannotBeInjuredException::class, false],
    'individual clear from injury' => [CannotBeClearedFromInjuryException::class, false],
    'individual restore' => [CannotBeRestoredException::class, false],
    'tag team employ' => [TagTeamCannotBeEmployedException::class, true],
    'tag team release' => [TagTeamCannotBeReleasedException::class, true],
    'tag team retire' => [TagTeamCannotBeRetiredException::class, true],
    'tag team unretire' => [TagTeamCannotBeUnretiredException::class, true],
    'tag team suspend' => [TagTeamCannotBeSuspendedException::class, true],
    'tag team reinstate' => [TagTeamCannotBeReinstatedException::class, true],
    'tag team restore' => [TagTeamCannotBeRestoredException::class, true],
]);

describe('roster error messages', function (): void {
    test('it resolves each lifecycle failure to its translated message', function (Closure $makeFailure, RosterEntityType $entityType, string $expectedKey): void {
        // Arrange
        $exception = $makeFailure();

        // Act
        $translationKey = RosterErrorMessageResolver::translationKey($exception, $entityType);

        // Assert
        expect($translationKey)->toBe($expectedKey)
            ->and(__($translationKey))->not->toBe($translationKey);
    })->with('roster lifecycle failures');

    test('it has a translated message for every mapped exception, reason and applicable roster entity', function (string $exceptionClass, bool $isTagTeamException): void {
        // Arrange
        if (! is_subclass_of($exceptionClass, BaseBusinessException::class)) {
            throw new LogicException("{$exceptionClass} is not a business exception.");
        }

        $entityTypes = $isTagTeamException
            ? [RosterEntityType::TagTeam]
            : [RosterEntityType::Wrestler, RosterEntityType::Manager, RosterEntityType::Referee];
        $untranslated = [];

        // Act
        foreach ($entityTypes as $entityType) {
            foreach (BusinessRuleReason::cases() as $reason) {
                $exception = new $exceptionClass('Test failure', reason: $reason);
                $translationKey = RosterErrorMessageResolver::translationKey($exception, $entityType);

                if (__($translationKey) === $translationKey) {
                    $untranslated[] = $translationKey;
                }
            }
        }

        // Assert
        expect($untranslated)->toBeEmpty();
    })->with('mapped roster exceptions');

    test('it keeps no roster error translation that no lifecycle failure can reach', function (RosterEntityType $entityType, array $reachableKeys): void {
        // Arrange
        $namespace = "{$entityType->translationNamespace()}.errors";
        $defaultKeys = collect(RosterLifecycleAction::cases())
            ->filter(fn (RosterLifecycleAction $action): bool => $action->supports($entityType))
            ->map(fn (RosterLifecycleAction $action): string => "{$namespace}.{$action->value}.default")
            ->all();
        // Restoring runs from the index tables rather than as a lifecycle action, but it resolves to its own default.
        $expectedKeys = [...$reachableKeys, ...$defaultKeys, "{$namespace}.restore.default", "{$namespace}.general"];

        // Act
        $translatedKeys = collect(Arr::dot(__($namespace)))
            ->keys()
            ->map(fn (string $key): string => "{$namespace}.{$key}")
            ->all();

        // Assert
        expect(array_diff($translatedKeys, $expectedKeys))->toBeEmpty()
            ->and(array_diff($expectedKeys, $translatedKeys))->toBeEmpty();
    })->with('roster entities with reachable translation keys');

    test('it renders the entity general message for an unmapped business exception', function (RosterEntityType $entityType): void {
        // Arrange
        $exception = CannotBeDeletedException::alreadyDeleted(new Wrestler(['name' => 'Test Wrestler']));

        // Act
        $translationKey = RosterErrorMessageResolver::translationKey($exception, $entityType);

        // Assert
        expect($translationKey)->toBe("{$entityType->translationNamespace()}.errors.general")
            ->and(__($translationKey))->toBe('An unexpected error occurred. Please try again.');
    })->with(RosterEntityType::cases());
});
