<?php

namespace App\Http\Requests\EventMatches;

use App\Models\EventMatch;
use App\Rules\CompetitorsAreValid;
use App\Rules\CompetitorsGroupedIntoCorrectNumberOfSidesForMatchType;
use App\Rules\TitleChampionIncludedInTitleMatch;
use App\Rules\TitlesMustBeActive;
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
            'competitors' => ['required', 'array', 'min:2'],
            'competitors.*' => ['required', 'array', 'min:1'],
            'competitors.*.*.competitor_id' => ['required', 'integer'],
            'competitors.*.*.competitor_type' => ['required', Rule::in(['wrestler', 'tag_team'])],
            'preview' => ['nullable', 'string'],
        ];
    }

    /**
     * Configure the validator instance.
     *
     * @param  \Illuminate\Validation\Validator  $validator
     *
     * @return void
     */
    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            if ($validator->errors()->isEmpty()) {
                $ruleA = new CompetitorsGroupedIntoCorrectNumberOfSidesForMatchType($this->input('match_type_id'));

                if (! $ruleA->passes('competitors', $this->input('competitors'))) {
                    $validator->addFailure(
                        'competitors',
                        CompetitorsGroupedIntoCorrectNumberOfSidesForMatchType::class
                    );
                }

                $rule2 = new CompetitorsAreValid;

                if (! $rule2->passes('competitors', $this->input('competitors'))) {
                    $validator->addFailure('competitors', CompetitorsAreValid::class);
                }

                if ($this->input('titles')) {
                    if (! (new TitlesMustBeActive)->passes('titles', $this->input('titles'))) {
                        $validator->addFailure('titles', TitlesMustBeActive::class);
                    }

                    if (! (new TitleChampionIncludedInTitleMatch($this->input('titles')))->passes('competitors', $this->input('competitors'))) {
                        $validator->addFailure('competitors', TitleChampionIncludedInTitleMatch::class);
                    }
                }
            }
        });

        return $validator->messages()->getMessages();
    }
}
