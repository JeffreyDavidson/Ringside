<?php

declare(strict_types=1);

namespace App\Http\Requests\TagTeams;

use App\Models\TagTeam;
use App\Rules\WrestlerCanJoinNewTagTeam;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Exists;
use Illuminate\Validation\Rules\Unique;
use Tests\RequestFactories\TagTeamRequestFactory;

class StoreRequest extends FormRequest
{
    /** @var class-string */
    public static string $factory = TagTeamRequestFactory::class;

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        if (is_null($this->user())) {
            return false;
        }

        return $this->user()->can('create', TagTeam::class);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, array<int, string|Exists|Unique|ValidationRule>>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'min:3', Rule::unique('tag_teams', 'name')],
            'signature_move' => ['nullable', 'string', 'regex:/^[a-zA-Z\s\']+$/'],
            'start_date' => ['nullable', 'string', 'date'],
            'wrestlerA' => [
                'nullable',
                'bail',
                'integer',
                'different:wrestlerB',
                'required_with:start_date',
                'required_with:wrestlerB',
                'required_with:signature_move',
                Rule::exists('wrestlers', 'id'),
                new WrestlerCanJoinNewTagTeam,
            ],
            'wrestlerB' => [
                'nullable',
                'bail',
                'integer',
                'different:wrestlerA',
                'required_with:start_date',
                'required_with:wrestlerA',
                'required_with:signature_move',
                Rule::exists('wrestlers', 'id'),
                new WrestlerCanJoinNewTagTeam,
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
