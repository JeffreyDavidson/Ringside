<?php

namespace App\Http\Requests\EventMatches;

use App\Models\EventMatch;
use App\Models\Title;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return $this->user()->can('create', EventMatch::class);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'match_type_id' => ['required', 'integer', Rule::exists('match_types', 'id')],
            'referees' => ['required', 'array'],
            'referees.*' => ['integer', 'distinct', Rule::exists('referees', 'id')],
            'titles' => ['nullable', 'array'],
            'titles.*' => ['integer', 'distinct', Rule::exists('titles', 'id')],
            'competitors' => ['required', 'array'],
            'competitors.*' => ['integer', 'distinct', Rule::exists('wrestlers', 'id')],
            'preview' => ['nullable', 'string'],
        ];
    }

    /**
     * Configure the validator instance.
     *
     * @param  \Illuminate\Validation\Validator  $validator
     * @return void
     */
    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            if ($validator->errors()->isEmpty()) {
                if (is_null($this->input('titles'))) {
                    return true;
                }

                foreach ($this->input('titles') as $titleId) {
                    $title = Title::find($titleId);

                    if (is_null($title->champion)) {
                        continue;
                    }

                    if (in_array($title->champion->id, $this->input('competitors'))) {
                        continue;
                    }

                    $validator->errors()->add('competitors', 'This match requires the champion to be involved.');
                }
            }
        });
    }
}
