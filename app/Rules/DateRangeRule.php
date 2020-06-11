<?php

namespace App\Rules;

use Illuminatech\Validation\Composite\CompositeRule;

class DateRangeRule extends CompositeRule
{
    protected function rules(): array
    {
        return ['string', 'min:8', 'max:200'];
    }

    protected function messages(): array
    {
        return [
            'string' => 'Only string is allowed.',
            'min' => ':attribute is too short.',
            'max' => ':attribute is too long.',
        ];
    }
}
