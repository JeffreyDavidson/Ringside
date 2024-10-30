<?php

declare(strict_types=1);

namespace App\Models\Contracts;

use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Carbon;

interface Activatable
{
    public function activations(): HasMany;

    public function currentActivation(): HasOne;

    public function futureActivation(): HasOne;

    public function previousActivations(): HasMany;

    public function previousActivation(): HasOne;

    public function hasActivations(): bool;

    public function isCurrentlyActivated(): bool;

    public function hasFutureActivation(): bool;

    public function isNotInActivation(): bool;

    public function isUnactivated(): bool;

    public function isDeactivated(): bool;

    public function activatedOn(Carbon $activationDate): bool;
}
