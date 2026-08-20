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
        'App\\Lifecycle\\IndividualRetirementEligibility',
        'App\\Lifecycle\\TitleLifecycleEligibility',
    ])
    ->toBeFinal();
