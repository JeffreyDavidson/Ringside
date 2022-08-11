<?php

declare(strict_types=1);

namespace App\Http\Requests\EventMatches;

use App\Models\EventMatch;
use App\Rules\CompetitorsAreValid;
use App\Rules\CompetitorsGroupedIntoCorrectNumberOfSidesForMatchType;
use App\Rules\TitleChampionIncludedInTitleMatch;
use App\Rules\TitleMustBeActive;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Tests\RequestFactories\EventMatchRequestFactory;
use Worksome\RequestFactories\Concerns\HasFactory;

class StoreRequest extends FormRequest
{
    use HasFactory;

    /** @var class-string */
    public static $factory = EventMatchRequestFactory::class;

    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        if (is_null($this->user())) {
            return false;
        }

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
            'titles' => ['array'],
            'titles.*' => ['bail', 'integer', 'distinct', Rule::exists('titles', 'id'), new TitleMustBeActive()],
            'competitors' => [
                'bail',
                'required',
                'array',
                'min:2',
                new CompetitorsGroupedIntoCorrectNumberOfSidesForMatchType($this->input('match_type_id')),
                new CompetitorsAreValid(),
                new TitleChampionIncludedInTitleMatch($this->collect('titles')),
            ],
            'competitors.*' => ['required', 'array', 'min:1'],
            'competitors.*.wrestlers' => ['array'],
            'competitors.*.tagteams' => ['array'],
            'preview' => ['nullable', 'string'],
        ];
    }
}
