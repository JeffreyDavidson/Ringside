<?php

declare(strict_types=1);

namespace App\Http\Requests\Stables;

use App\Models\Stable;
use App\Rules\Stables\ActivationStartDateCanBeChanged;
use App\Rules\Stables\HasMinimumAmountOfMembers;
use App\Rules\TagTeamCanJoinExistingStable;
use App\Rules\WrestlerCanJoinExistingStable;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\RequiredIf;
use Tests\RequestFactories\StableRequestFactory;
use Worksome\RequestFactories\Concerns\HasFactory;

class UpdateRequest extends FormRequest
{
    use HasFactory;

    public static $factory = StableRequestFactory::class;

    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return $this->user()->can('update', Stable::class);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        $stable = $this->route()->parameter('stable');

        return [
            'name' => ['required', 'string', 'min:3', Rule::unique('stables')->ignore($stable->id)],
            'started_at' => [
                'nullable',
                Rule::requiredIf($stable->isCurrentlyActivated()),
                'string',
                'date',
                new ActivationStartDateCanBeChanged($stable),
            ],
            'members_count' => [
                'bail',
                'integer',
                Rule::when($this->input('started_at'),
                new HasMinimumAmountOfMembers(
                    $stable,
                    $this->input('started_at'),
                    $this->collect('wrestlers'),
                    $this->collect('tag_teams'))
                ),
            ],
            'wrestlers' => ['array'],
            'tag_teams' => ['array'],
            'wrestlers.*' => [
                'bail',
                'integer',
                'distinct',
                Rule::exists('wrestlers', 'id'),
                new WrestlerCanJoinExistingStable($this->input('tag_teams'), $this->input('started_at')),
            ],
            'tag_teams.*' => [
                'bail',
                'integer',
                'distinct',
                Rule::exists('tag_teams', 'id'),
                new TagTeamCanJoinExistingStable(),
            ],
        ];
    }

    /**
     * Prepare the data for validation.
     *
     * @return void
     */
    protected function prepareForValidation()
    {
        $this->merge([
            'members_count' => (count($this->input('tag_teams', [])) * 2) + count($this->input('wrestlers', [])),
        ]);
    }
}
