<?php

declare(strict_types=1);

namespace App\Http\Requests\Stables;

use App\Models\Stable;
use App\Rules\LetterSpace;
use App\Rules\TagTeamCanJoinNewStable;
use App\Rules\WrestlerCanJoinNewStable;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Tests\RequestFactories\StableRequestFactory;

class StoreRequest extends FormRequest
{
    /** @var class-string */
    public static $factory = StableRequestFactory::class;

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        if (is_null($this->user())) {
            return false;
        }

        return $this->user()->can('create', Stable::class);
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', new LetterSpace, 'min:3', Rule::unique('stables', 'name')],
            'start_date' => ['nullable', 'string', 'date'],
            'members_count' => ['nullable', 'integer', Rule::when((bool) $this->input('start_date'), 'min:3')],
            'wrestlers' => ['array'],
            'tag_teams' => ['array'],
            'managers' => ['array'],
            'wrestlers.*' => [
                'bail',
                'integer',
                'distinct',
                Rule::exists('wrestlers', 'id'),
                new WrestlerCanJoinNewStable($this->collect('tag_teams')),
            ],
            'tag_teams.*' => [
                'bail',
                'integer',
                'distinct',
                Rule::exists('tag_teams', 'id'),
                new TagTeamCanJoinNewStable(),
            ],
            'managers.*' => [
                'bail',
                'integer',
                'distinct',
                Rule::exists('managers', 'id'),
            ],
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        $wrestlersCount = count($this->collect('wrestlers'));
        $tagTeamsCount = count($this->collect('tag_teams')) * 2;

        $this->mergeIfMissing([
            'members_count' => $tagTeamsCount + $wrestlersCount,
        ]);
    }
}
