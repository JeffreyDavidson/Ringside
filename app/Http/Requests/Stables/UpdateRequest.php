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
use Tests\RequestFactories\StableRequestFactory;
use Worksome\RequestFactories\Concerns\HasFactory;

class UpdateRequest extends FormRequest
{
    use HasFactory;

    /** @var class-string */
    public static $factory = StableRequestFactory::class;

    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        if (is_null($this->user()) || is_null($this->route())) {
            return false;
        }

        if (! $this->route()->hasParameter('stable') || is_null($this->route()->parameter('stable'))) {
            return false;
        }

        return $this->user()->can('update', Stable::class);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        /** @var \App\Models\Stable */
        $stable = $this->route()->parameter('stable');

        return [
            'name' => ['required', 'string', 'min:3', Rule::unique('stables')->ignore($stable->id)],
            'start_date' => [
                'nullable',
                Rule::requiredIf($stable->isCurrentlyActivated()),
                'string',
                'date',
                new ActivationStartDateCanBeChanged($stable),
            ],
            'members_count' => [
                'bail',
                'integer',
                Rule::when(
                    $this->input('start_date'),
                    function () use ($stable) {
                        new HasMinimumAmountOfMembers(
                            $stable,
                            $this->date('start_date'),
                            $this->collect('wrestlers'),
                            $this->collect('tag_teams')
                        );
                    }
                ),
            ],
            'wrestlers' => ['array'],
            'tag_teams' => ['array'],
            'managers' => ['array'],
            'wrestlers.*' => [
                'bail',
                'integer',
                'distinct',
                Rule::exists('wrestlers', 'id'),
                new WrestlerCanJoinExistingStable($this->input('tag_teams'), $this->input('start_date')),
            ],
            'tag_teams.*' => [
                'bail',
                'integer',
                'distinct',
                Rule::exists('tag_teams', 'id'),
                new TagTeamCanJoinExistingStable($this->date('start_date')),
            ],
            'managers' => [
                'bail',
                'integer',
                'distinct',
                Rule::exists('managers', 'id'),
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
            'members_count' => ($this->collect('tag_teams')->count() * 2) + $this->collect('wrestlers')->count(),
        ]);

        // dd($this->all());
    }
}
