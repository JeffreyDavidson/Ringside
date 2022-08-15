<?php

declare(strict_types=1);

namespace App\Http\Requests\Stables;

use App\Models\Stable;
use App\Rules\TagTeamCanJoinNewStable;
use App\Rules\WrestlerCanJoinNewStable;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Tests\RequestFactories\StableRequestFactory;
use Worksome\RequestFactories\Concerns\HasFactory;

class StoreRequest extends FormRequest
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
        if (is_null($this->user())) {
            return false;
        }

        return $this->user()->can('create', Stable::class);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'name' => ['required', 'string', 'min:3', Rule::unique('stables', 'name')],
            'start_date' => ['nullable', 'string', 'date'],
            'members_count' => ['nullable', 'integer', Rule::when($this->input('start_date'), 'min:3')],
            'wrestlers' => ['array'],
            'tag_teams' => ['array'],
            'managers' => ['array'],
            'wrestlers.*' => [
                'bail',
                'integer',
                'distinct',
                Rule::exists('wrestlers', 'id'),
                new WrestlerCanJoinNewStable($this->input('tag_teams')),
            ],
            'tag_teams.*' => [
                'bail',
                'integer',
                'distinct',
                Rule::exists('tag_teams', 'id'),
                new TagTeamCanJoinNewStable(),
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
        $wrestlersCount = count($this->input('wrestlers', []));
        $tagTeamsCount = count($this->input('tag_teams', [])) * 2;

        $this->mergeIfMissing([
            'members_count' => $tagTeamsCount + $wrestlersCount,
        ]);
    }
}
