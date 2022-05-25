<?php

declare(strict_types=1);

namespace App\Http\Requests\Stables;

use App\Models\Stable;
use App\Rules\TagTeamCanJoinStable;
use App\Rules\WrestlerCanJoinStable;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Tests\RequestFactories\StableRequestFactory;
use Worksome\RequestFactories\Concerns\HasFactory;

class StoreRequest extends FormRequest
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
            'started_at' => ['nullable', 'string', 'date'],
            'members_count' => ['nullable', 'integer', 'min:3'],
            'wrestlers' => ['array'],
            'tag_teams' => ['array'],
            'wrestlers.*' => [
                'bail',
                'integer',
                'distinct',
                Rule::exists('wrestlers', 'id'),
                new WrestlerCanJoinStable($this->input('tag_teams')),
            ],
            'tag_teams.*' => [
                'bail',
                'integer',
                'distinct',
                Rule::exists('tag_teams', 'id'),
                new TagTeamCanJoinStable(),
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
            'members_count' => (count($this->input('tag_teams')) * 2) + count($this->input('wrestlers')),
        ]);
    }

    protected function requestHasMembers()
    {
        return $this->collect('tag_teams')->isNotEmpty() || $this->collect('wrestlers')->isNotEmpty();
    }
}
