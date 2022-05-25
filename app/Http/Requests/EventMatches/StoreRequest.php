<?php

declare(strict_types=1);

namespace App\Http\Requests\EventMatches;

use App\Models\EventMatch;
use App\Rules\CompetitorsAreValid;
use App\Rules\CompetitorsGroupedIntoCorrectNumberOfSidesForMatchType;
use App\Rules\TitleChampionIncludedInTitleMatch;
use App\Rules\TitlesMustBeActive;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Tests\RequestFactories\EventMatchRequestFactory;
use Worksome\RequestFactories\Concerns\HasFactory;

class StoreRequest extends FormRequest
{
    use HasFactory;

    public static $factory = EventMatchRequestFactory::class;

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
            'titles' => ['nullable', 'array', new TitlesMustBeActive()],
            'titles.*' => ['integer', 'distinct', Rule::exists('titles', 'id')],
            'competitors' => [
                'required',
                'array',
                'min:2',
                new CompetitorsGroupedIntoCorrectNumberOfSidesForMatchType($this->input('match_type_id')),
                new CompetitorsAreValid(),
                new TitleChampionIncludedInTitleMatch($this->input('titles')),
            ],
            'competitors.*' => ['required', 'array', 'min:1'],
            'competitors.*.*.competitor_id' => ['required', 'integer'],
            'competitors.*.*.competitor_type' => ['required', Rule::in(['wrestler', 'tag_team'])],
            'preview' => ['nullable', 'string'],
        ];
    }
}
