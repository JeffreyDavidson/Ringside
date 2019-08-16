<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AjaxOnlyFormRequest extends FormRequest
{
    public function validateResolved()
    {
        if ($this->ajax()) {
            parent::validateResolved();
        }
    }
}
