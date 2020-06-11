<?php

namespace App\Models;

use App\Traits\HasCachedAttributes;
use Illuminate\Database\Eloquent\Model;

abstract class SingleRosterMember extends Model
{
    use HasCachedAttributes,
        Concerns\CanBeSuspended,
        Concerns\CanBeInjured,
        Concerns\CanBeRetired,
        Concerns\CanBeEmployed,
        Concerns\CanBeBooked;

    public function retire($retiredAt = null)
    {
        if ($this->isSuspended()) {
            $this->reinstate();
        }

        if ($this->isInjured()) {
            $this->clearFromInjury();
        }

        $retiredDate = $retiredAt ?: now();
        $this->currentEmployment()->update(['ended_at' => $retiredDate]);

        $this->retirements()->create(['started_at' => $retiredDate]);

        return $this->touch();
    }
}
