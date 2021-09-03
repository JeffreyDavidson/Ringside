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

    public function passes($attribute, $value)
    {
        if ($this->model->isUnactivated()) {
            return true;
        }

        if ($this->model->hasFutureActivation()) {
            return true;
        }

        return false;
    }

    public function message()
    {
        return 'The :attribute field cannot be changed to a date after its been introduced.';
    }
}
