<?php

namespace App\Http\Requests\Titles;

use App\Exceptions\CannotBeRetiredException;
use Illuminate\Foundation\Http\FormRequest;

class RetireRequest extends FormRequest
{
    public function authorize()
    {
        $title = $this->route('title');

        if ($this->user()->cannot('retire', $title)) {
            return false;
        }

        if (! $title->canBeRetired()) {
            throw new CannotBeRetiredException();
        }

        return true;
    }

    public function rules()
    {
        return [];
    }
}
