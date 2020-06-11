<?php

namespace App\Rules;

use Carbon\Carbon;
use Illuminate\Contracts\Validation\Rule;
use Illuminate\Database\Eloquent\Model;

class ActivationStartDateCanBeChanged implements Rule
{
    protected $model;

    public function __construct(Model $activatable)
    {
        $this->model = $activatable;
    }

    public function passes($attribute, $value = null)
    {
        /**
         *  Times when activation date can/cannot be changed.
         *
         * * If model has a current activation then it cannot be changed.
         * * If model has a future activation and the value is null then it can be changed.
         * * If model has a future activation and value is before future activation
         * *   start date then start date can be changed.
         */
        $currentActivation = $this->model->currentActivation;
        $futureActivation = $this->model->futureActivation;
        $formDate = Carbon::parse($value);

        if ($currentActivation) {
            if ($formDate === null) {
                return false;
            }

            if ($formDate->lte($currentActivation->started_at)) {
                return true;
            }

            return false;
        }

        if ($futureActivation) {
            if ($formDate === null) {
                return true;
            }

            if ($formDate->isFuture()) {
                return true;
            }

            if ($formDate->lte($futureActivation->started_at)) {
                return true;
            }
        }

        if ((! $futureActivation) && (! $currentActivation)) {
            return true;
        }

        return false;
    }

    public function message()
    {
        return 'The :attribute field cannot be changed to a date after its been introduced.';
    }
}
