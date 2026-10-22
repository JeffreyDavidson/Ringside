<?php

declare(strict_types=1);

use App\Lifecycle\Events\SchedulingSlotLock;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

/*
 * A Query only reads (laravel-conventions rule queries.md). Locks and guards that throw live in Lifecycle, validation in the form or
 * request that feeds the Query, 404s in controllers and URLs in controllers, components and ViewModels.
 */
arch('queries use the query suffix')
    ->expect('App\Queries')
    ->toHaveSuffix('Query');

arch('queries do not validate')
    ->expect('App\Queries')
    ->not->toUse([Validator::class, ValidationException::class, 'Illuminate\Contracts\Validation']);

arch('queries do not throw business exceptions')
    ->expect('App\Queries')
    ->not->toUse('App\Exceptions');

arch('queries do not depend on the view layer')
    ->expect('App\Queries')
    ->not->toUse(['App\ViewModels', 'App\Presenters', 'App\Livewire']);

arch('queries take no locks')
    ->expect('App\Queries')
    ->not->toUse(['App\Lifecycle\Roster\Booking', SchedulingSlotLock::class]);

arch('queries leave HTTP responses, redirects and URLs to the caller')
    ->expect('App\Queries')
    ->not->toUse(['abort', 'abort_if', 'abort_unless', 'route', 'redirect', 'to_route', 'response', 'url', 'Illuminate\Http']);
