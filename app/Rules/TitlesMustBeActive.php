<?php

namespace App\Rules;

use App\Enums\TitleStatus;
use App\Models\Title;
use Illuminate\Contracts\Validation\Rule;
use Illuminate\Database\Eloquent\Collection;

class TitlesMustBeActive implements Rule
{
    /**
     * @var Collection
     */
    private $inactiveTitleNames;

    public function __construct()
    {
        $this->inactiveTitleNames = collect();
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
        $nonActiveTitles = Title::where('status', '!=', TitleStatus::active())->findMany($value);

        if ($nonActiveTitles->isEmpty()) {
            return true;
        }

        foreach ($nonActiveTitles as $title) {
            $this->inactiveTitleNames->push($title->name);
        }

        return false;
    }

    /**
     * Get the validation error message.
     *
     * @return string
     */
    public function message()
    {
        if ($this->inactiveTitleNames->count() == 1) {
            return $this->inactiveTitleNames->implode(',').' is not an active title and cannot be added to the match';
        }

        return $this->inactiveTitleNames->implode(', ').' are not active titles and cannot be added to the match.';
    }
}
