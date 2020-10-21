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
     * Undocumented function.
     *
     * @param [type] $attribute
     * @param [type] $value
     * @return bool
     */
    public function passes($attribute, $value)
    {
        /*
         *  Times when employment date can/cannot be changed.
         *
         * * If model has a current employment then it cannot be changed.
         * * If model has a future employment then the value can be changed.
         * * If model is unemployed then value skip.
         *
         */

        if ($this->model->isUnemployed()) {
            return true;
        }

        $currentEmployment = $this->model->currentEmployment;
        $futureEmployment = $this->model->futureEmployment;
        $formDate = Carbon::parse($value);

        if ($currentEmployment) {
            if ($formDate === null) {
                return false;
            }

            if ($formDate->lte($currentEmployment->started_at)) {
                return true;
            }

            return false;
        }

        if ($futureEmployment) {
            if ($formDate === null) {
                return true;
            }

            if ($formDate->isFuture()) {
                return true;
            }

            if ($formDate->lte($futureEmployment->started_at)) {
                return true;
            }
        }

        if ((! $futureEmployment) && (! $currentEmployment)) {
            return true;
        }

        return false;
    }

    public function message()
    {
        return 'The :attribute field cannot be changed to a date after its been employed.';
    }
}
