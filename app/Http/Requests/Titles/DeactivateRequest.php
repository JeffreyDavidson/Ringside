<?php

namespace App\Http\Requests\Titles;

use App\Exceptions\CannotBeDeactivatedException;
use Illuminate\Foundation\Http\FormRequest;

class DeactivateRequest extends FormRequest
{
    public function authorize()
    {
        $title = $this->route('title');

        if ($this->user()->cannot('deactivate', $title)) {
            return false;
        }

        if (! $title->canBeDeactivated()) {
            throw new CannotBeDeactivatedException();
        }

        return true;
    }

    public function rules()
    {
        return [];
    }
}
