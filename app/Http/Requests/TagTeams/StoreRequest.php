<?php

declare(strict_types=1);

namespace App\Http\Requests\TagTeams;

use App\Models\TagTeam;
use App\Rules\LetterSpace;
use App\Rules\WrestlerCanJoinNewTagTeam;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Tests\RequestFactories\TagTeamRequestFactory;
use Worksome\RequestFactories\Concerns\HasFactory;

class StoreRequest extends FormRequest
{
    use HasFactory;

    /** @var class-string */
    public static $factory = TagTeamRequestFactory::class;

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
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', new LetterSpace, 'min:3', Rule::unique('tag_teams', 'name')],
            'signature_move' => ['nullable', 'string', 'regex:/^[a-zA-Z\s\']+$/'],
            'start_date' => ['nullable', 'string', 'date'],
            'wrestlerA' => [
                'nullable',
                'integer',
                'different:wrestlerB',
                'required_with:start_date',
                'required_with:wrestlerB',
                Rule::exists('wrestlers', 'id'),
                new WrestlerCanJoinNewTagTeam,
            ],
            'wrestlerB' => [
                'nullable',
                'integer',
                'different:wrestlerA',
                'required_with:start_date',
                'required_with:wrestlerA',
                Rule::exists('wrestlers', 'id'),
                new WrestlerCanJoinNewTagTeam,
            ],
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'signature_move.regex' => 'The signature move only allows for letters, spaces, and apostrophes',
        ];
    }

    /**
     * Get custom attributes for validator errors.
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
