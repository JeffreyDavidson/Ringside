<?php

namespace App\Http\Requests\Titles;

use App\Exceptions\CannotBeUnretiredException;
use Illuminate\Foundation\Http\FormRequest;

class UnretireRequest extends FormRequest
{
    public function authorize()
    {
        $title = $this->route('title');

        if ($this->user()->cannot('unretire', $title)) {
            return false;
        }

        if (! $title->canBeUnretired()) {
            throw new CannotBeUnretiredException();
        }

        return true;
    }

    public function rules()
    {
        return [];
    }
}
