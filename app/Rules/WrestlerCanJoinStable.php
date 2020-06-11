<?php

namespace App\Rules;

use App\Models\Stable;
use App\Models\Wrestler;
use Illuminate\Contracts\Validation\Rule;

class WrestlerCanJoinStable implements Rule
{
    /**
     * @var \App\Models\Stable
     */
    protected $stable;

    /**
     * Undocumented function
     *
     * @param \App\Models\Stable $stable
     */
    public function __construct(Stable $stable)
    {
        $this->stable = $stable;
    }

    /**
     * Determine if the validation rule passes.
     *
     * @param  string  $attribute
     * @param  mixed  $value
     * @return bool
     */
    public function passes($attribute, $value)
    {
        $wrestler = Wrestler::find($value);

        if (! $wrestler) {
            return false;
        }

        if (!data_get($wrestler, 'currentEmployment.started_at')) {
            return false;
        }

        if ($wrestler->currentEmployment->started_at->isFuture()) {
            return false;
        }

        if (!$wrestler->is_bookable) {
            return false;
        }

        // We need to to make sure the wrestler isn't in any
        // bookable stables excluding the current stable.
        if ($wrestler->stables()->bookable()->whereKeyNot($this->stable->id)->exists()) {
            return false;
        }

        return true;
    }

    /**
     * Get the validation error message.
     *
     * @return string
     */
    public function message()
    {
        return 'This wrestler cannot join this stable.';
    }
}
