<?php

declare(strict_types=1);

use Illuminate\Contracts\Validation\ValidationRule;

arch()->preset()->php();
arch()->preset()->security();

arch('application code uses strict types')
    ->expect('App')
    ->toUseStrictTypes();

arch('application code does not depend on tests')
    ->expect('App')
    ->not->toUse('Tests');

arch('application code does not use debug helpers')
    ->expect(['dd', 'dump', 'ray', 'var_dump'])
    ->each->not->toBeUsed();

arch('environment values are read through configuration')
    ->expect('env')
    ->not->toBeUsed();

arch('enums are native enums')
    ->expect('App\\Enums')
    ->toBeEnums();

arch('controllers use the controller suffix')
    ->expect('App\\Http\\Controllers')
    ->toHaveSuffix('Controller');

arch('actions use the action suffix')
    ->expect('App\\Actions')
    ->toHaveSuffix('Action');

arch('services use the service suffix')
    ->expect('App\\Services')
    ->toHaveSuffix('Service');

arch('custom validation rules implement Laravel validation rules')
    ->expect('App\\Rules')
    ->toImplement(ValidationRule::class);

arch('lifecycle eligibility policies are final')
    ->expect([
        \App\Lifecycle\Events\EventSchedulingEligibility::class,
        \App\Lifecycle\Roster\Individuals\IndividualDeletionEligibility::class,
        \App\Lifecycle\Roster\Individuals\IndividualEmploymentEligibility::class,
        \App\Lifecycle\Roster\Individuals\IndividualInjuryEligibility::class,
        \App\Lifecycle\Roster\Individuals\IndividualRetirementEligibility::class,
        \App\Lifecycle\Roster\Individuals\IndividualSuspensionEligibility::class,
        \App\Lifecycle\Roster\Stables\StableActivityEligibility::class,
        \App\Lifecycle\Roster\Stables\StableDeletionEligibility::class,
        \App\Lifecycle\Roster\Stables\StableFormerMemberEligibility::class,
        \App\Lifecycle\Roster\Stables\StableRestructuringEligibility::class,
        \App\Lifecycle\Roster\Stables\StableRetirementEligibility::class,
        \App\Lifecycle\Roster\TagTeams\TagTeamDeletionEligibility::class,
        \App\Lifecycle\Roster\TagTeams\TagTeamEmploymentEligibility::class,
        \App\Lifecycle\Roster\TagTeams\TagTeamRetirementEligibility::class,
        \App\Lifecycle\Roster\TagTeams\TagTeamSuspensionEligibility::class,
        \App\Lifecycle\Titles\TitleDeletionEligibility::class,
        \App\Lifecycle\Titles\TitleLifecycleEligibility::class,
        \App\Lifecycle\Venues\VenueDeletionEligibility::class,
        \App\Lifecycle\Venues\VenueSchedulingEligibility::class,
    ])
    ->toBeFinal();
