<?php

declare(strict_types=1);

namespace App\Http\Requests\TagTeams;

use App\Models\TagTeam;
use App\Rules\EmploymentStartDateCanBeChanged;
use App\Rules\WrestlerCanJoinExistingTagTeam;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Exists;
use Illuminate\Validation\Rules\Unique;
use Tests\RequestFactories\TagTeamRequestFactory;
use Worksome\RequestFactories\RequestFactory;

class UpdateRequest extends FormRequest
{
    /** @var class-string */
    public static string $factory = TagTeamRequestFactory::class;

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        if (is_null($this->user()) || is_null($this->route())) {
            return false;
        }

        if (! $this->route()->hasParameter('tag_team') || is_null($this->route()->parameter('tag_team'))) {
            return false;
        }

        return $this->user()->can('update', TagTeam::class);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, array<int, string|Exists|Unique|ValidationRule>>
     */
    public function rules(): array
    {
        /** @var \App\Models\TagTeam $tagTeam */
        $tagTeam = $this->route()?->parameter('tag_team');

        return [
            'name' => ['required', 'string', 'min:3', Rule::unique('tag_teams')->ignore($tagTeam->id)],
            'signature_move' => ['nullable', 'string', 'regex:/^[a-zA-Z\s\']+$/'],
            'start_date' => ['nullable', 'string', 'date', new EmploymentStartDateCanBeChanged($tagTeam)],
            'wrestlerA' => [
                'nullable',
                'integer',
                'different:wrestlerB',
                'required_with:wrestlerB',
                Rule::exists('wrestlers', 'id'),
                new WrestlerCanJoinExistingTagTeam($tagTeam),
            ],
            'wrestlerB' => [
                'nullable',
                'integer',
                'different:wrestlerA',
                'required_with:wrestlerB',
                Rule::exists('wrestlers', 'id'),
                new WrestlerCanJoinExistingTagTeam($tagTeam),
            ],
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'signature_move.regex' => 'The signature move only allows for letters, spaces, and apostrophes',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     *
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'signature_move' => 'signature move',
            'start_date' => 'start date',
            'wrestlerA' => 'tag team partner 1',
            'wrestlerB' => 'tag team partner 2',
        ];
    }
}
