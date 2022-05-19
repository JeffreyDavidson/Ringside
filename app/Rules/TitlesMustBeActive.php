<?php

declare(strict_types=1);

namespace App\Rules;

use App\Enums\TitleStatus;
use App\Models\Title;
use Illuminate\Contracts\Validation\Rule;

class TitlesMustBeActive implements Rule
{
    /**
     * Determine if the validation rule passes.
     *
     * @param  string  $attribute
     * @param  mixed  $value
     * @return bool
     */
    public function passes($attribute, $value)
    {
        $nonActiveTitlesNames = Title::query()
            ->where('status', '!=', TitleStatus::active())
            ->findMany($value)
            ->pluck('name');

        $message = $nonActiveTitlesNames->implode(', ').' are not active titles and cannot be added to the match.';

        if ($nonActiveTitlesNames->isNotEmpty()) {
            $this->fail($message);
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
        return $this->message;
    }

    /**
     * Adds the given failures, and return false.
     *
     * @param  array|string  $messages
     * @return bool
     */
    protected function fail($message)
    {
        $this->message = $message;

        return false;
    }
}
