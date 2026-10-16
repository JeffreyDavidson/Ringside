<?php

declare(strict_types=1);

use Illuminate\Container\Attributes\DB as DBConnection;
use Illuminate\Database\Connection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

/*
 * A Service only reads (.ai/rules/services.md): cross-record calculations and lookups, plus the request's promotion
 * context. Guards that throw, lifecycle writers, row locks and validation live in Lifecycle, Actions and the form that
 * feeds the save.
 */
arch('services do not validate')
    ->expect('App\Services')
    ->not->toUse([Validator::class, ValidationException::class, 'Illuminate\Contracts\Validation']);

arch('services do not throw business exceptions')
    ->expect('App\Services')
    ->not->toUse('App\Exceptions');

arch('services do not depend on lifecycle guards, writers or locks')
    ->expect('App\Services')
    ->not->toUse('App\Lifecycle');

arch('services do not open transactions or take locks through the connection')
    ->expect('App\Services')
    ->not->toUse([DB::class, Connection::class, DBConnection::class]);

arch('services do not depend on actions')
    ->expect('App\Services')
    ->not->toUse('App\Actions');

arch('services do not depend on the view layer')
    ->expect('App\Services')
    ->not->toUse(['App\ViewModels', 'App\Presenters', 'App\Livewire']);
