<?php

namespace App\Rules;

use App\Models\Stable;
use App\Models\Wrestler;
use Illuminate\Contracts\Validation\Rule;

class WrestlerCanJoinStable implements Rule
{
    protected $stable;

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

        if (! $wrestler) return false;

        if ($wrestler->hired_at->isFuture()) {
            return false;
        }

        if (!$wrestler->is_active) {
            return false;
        }

        if ($wrestler->whereHas('stables', function ($query) {
            $query->where('is_active', true)->whereKeyNot($this->stable->id);
        })->exists()) {
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
