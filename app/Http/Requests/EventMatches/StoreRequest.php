<?php

declare(strict_types=1);

namespace App\Http\Requests\EventMatches;

use App\Models\EventMatch;
use App\Rules\CompetitorsAreNotDuplicated;
use App\Rules\CompetitorsGroupedIntoCorrectNumberOfSidesForMatchType;
use App\Rules\RefereeCanRefereeMatch;
use App\Rules\TagTeamMustBeBookable;
use App\Rules\TitleChampionIncludedInTitleMatch;
use App\Rules\TitleMustBeActive;
use App\Rules\WrestlerMustBeBookable;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Exists;
use Tests\RequestFactories\EventMatchRequestFactory;

class StoreRequest extends FormRequest
{
    /** @var class-string */
    public static $factory = EventMatchRequestFactory::class;

    protected $stopOnFirstFailure = true;

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        if (is_null($this->user())) {
            return false;
        }

        return $this->user()->can('create', EventMatch::class);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, array<int, string|Exists|ValidationRule>>
     */
    public function rules(): array
    {
        return [
            'match_type_id' => ['required', 'integer', Rule::exists('match_types', 'id')],
            'referees' => ['required', 'array'],
            'referees.*' => ['bail', 'integer', 'distinct', Rule::exists('referees', 'id'), new RefereeCanRefereeMatch()],
            'titles' => ['array'],
            'titles.*' => ['bail', 'integer', 'distinct', Rule::exists('titles', 'id'), new TitleMustBeActive()],
            'competitors' => [
                'bail',
                'required',
                'array',
                'min:2',
                new CompetitorsGroupedIntoCorrectNumberOfSidesForMatchType(),
                new CompetitorsAreNotDuplicated(),
                new TitleChampionIncludedInTitleMatch(),
            ],
            'competitors.*' => ['required', 'array'],
            'competitors.*.wrestlers' => ['array'],
            'competitors.*.wrestlers.*' => [
                'bail',
                'integer',
                'distinct',
                Rule::exists('wrestlers', 'id'),
                new WrestlerMustBeBookable(),
            ],
            'competitors.*.tagteams' => ['array'],
            'competitors.*.tagteams.*' => [
                'bail',
                'integer',
                'distinct',
                Rule::exists('tag_teams', 'id'),
                new TagTeamMustBeBookable(),
            ],
            'preview' => ['nullable', 'string'],
        ];
    }
}
