<?php

namespace App\Rules;

use Carbon\Carbon;
use Illuminate\Contracts\Validation\Rule;
use Illuminate\Database\Eloquent\Model;

class EmploymentStartDateCanBeChanged implements Rule
{
    /** @var */
    protected $model;

    /**
     * Undocumented function.
     *
     * @param Model $employable
     */
    public function __construct(Model $employable)
    {
        $this->model = $employable;
    }

    /**
     * Determine if the validation rule passes.
     *
     * @param  string $attribute
     * @param  string $value
     * @return bool
     */
    public function passes($attribute, $value)
    {
        if ($this->model->currentEmployment()->doesntExist() || $this->model->currentEmployment->started_at->eq(Carbon::parse($value))) {
            return true;
        }

        return false;
    }

    public function message()
    {
        return 'The :attribute field cannot be changed to a date after its been employed.';
    }
}
