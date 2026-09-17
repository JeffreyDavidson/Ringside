<?php

declare(strict_types=1);

use App\Lifecycle\Events\EventSchedulingEligibility;
use App\Lifecycle\Roster\Individuals\IndividualDeletionEligibility;
use App\Lifecycle\Roster\Individuals\IndividualEmploymentEligibility;
use App\Lifecycle\Roster\Individuals\IndividualInjuryEligibility;
use App\Lifecycle\Roster\Individuals\IndividualRetirementEligibility;
use App\Lifecycle\Roster\Individuals\IndividualSuspensionEligibility;
use App\Lifecycle\Roster\Stables\StableActivityEligibility;
use App\Lifecycle\Roster\Stables\StableDeletionEligibility;
use App\Lifecycle\Roster\Stables\StableFormerMemberEligibility;
use App\Lifecycle\Roster\Stables\StableRestructuringEligibility;
use App\Lifecycle\Roster\Stables\StableRetirementEligibility;
use App\Lifecycle\Roster\TagTeams\TagTeamDeletionEligibility;
use App\Lifecycle\Roster\TagTeams\TagTeamEmploymentEligibility;
use App\Lifecycle\Roster\TagTeams\TagTeamRetirementEligibility;
use App\Lifecycle\Roster\TagTeams\TagTeamSuspensionEligibility;
use App\Lifecycle\Titles\TitleDeletionEligibility;
use App\Lifecycle\Titles\TitleLifecycleEligibility;
use App\Lifecycle\Venues\VenueDeletionEligibility;
use App\Lifecycle\Venues\VenueSchedulingEligibility;
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
        EventSchedulingEligibility::class,
        IndividualDeletionEligibility::class,
        IndividualEmploymentEligibility::class,
        IndividualInjuryEligibility::class,
        IndividualRetirementEligibility::class,
        IndividualSuspensionEligibility::class,
        StableActivityEligibility::class,
        StableDeletionEligibility::class,
        StableFormerMemberEligibility::class,
        StableRestructuringEligibility::class,
        StableRetirementEligibility::class,
        TagTeamDeletionEligibility::class,
        TagTeamEmploymentEligibility::class,
        TagTeamRetirementEligibility::class,
        TagTeamSuspensionEligibility::class,
        TitleDeletionEligibility::class,
        TitleLifecycleEligibility::class,
        VenueDeletionEligibility::class,
        VenueSchedulingEligibility::class,
    ])
    ->toBeFinal();
