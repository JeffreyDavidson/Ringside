<?php

declare(strict_types=1);

namespace App\Rules;

use App\Models\Title;
use Illuminate\Contracts\Validation\Rule;

class TitleMustBeActive implements Rule
{
    protected $messages;

    /**
     * Determine if the validation rule passes.
     *
     * @param  string  $attribute
     * @param  mixed  $value
     * @return bool
     */
    public function passes($attribute, $value)
    {
        $title = Title::query()->find($value);

        if (! $title->isCurrentlyActivated()) {
            return false;
        }

        return true;
    }

    /**
     * Get the validation error message.
     *
     * @return array
     */
    public function message()
    {
        return 'This title is not active and cannot be added to the match.';
    }
}
