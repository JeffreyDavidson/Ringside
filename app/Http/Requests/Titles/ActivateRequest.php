<?php

namespace App\Http\Requests\Titles;

use App\Exceptions\CannotBeActivatedException;
use Illuminate\Foundation\Http\FormRequest;

class ActivateRequest extends FormRequest
{
    public function authorize()
    {
        $title = $this->route('title');

        if ($this->user()->cannot('activate', $title)) {
            return false;
        }

        if (! $title->canBeActivated()) {
            throw new CannotBeActivatedException();
        }

        return true;
    }

    public function rules()
    {
        return [];
    }
}
