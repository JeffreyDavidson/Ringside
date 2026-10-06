<?php

declare(strict_types=1);

use App\Models\Promotions\PromotionInvitation;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Schedule;

/*
|--------------------------------------------------------------------------
| Console Routes
|--------------------------------------------------------------------------
|
| This file is where you may define all of your Closure based console
| commands. Each Closure is bound to a command instance allowing a
| simple approach to interacting with each command's IO methods.
|
*/

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->describe('Display an inspiring quote');

// Every write to nine models is logged, so prune the log daily. --force is required because the command asks for
// confirmation in production and a scheduled run cannot answer. Retention is the package's clean_after_days (365).
Schedule::command('activitylog:clean', ['--force'])->daily();

// Expiry is enforced whenever invitations are read; this only deletes the rows that have expired.
Schedule::command('model:prune', ['--model' => [PromotionInvitation::class]])->daily();
