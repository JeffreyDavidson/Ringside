<?php

declare(strict_types=1);

namespace App\Http\Requests\TagTeams;

use App\Models\TagTeam;
use App\Rules\EmploymentStartDateCanBeChanged;
use App\Rules\WrestlerCanJoinExistingTagTeam;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Tests\RequestFactories\TagTeamRequestFactory;
use Worksome\RequestFactories\Concerns\HasFactory;

class UpdateRequest extends FormRequest
{
    use HasFactory;

    public static $factory = TagTeamRequestFactory::class;

    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return $this->user()->can('update', TagTeam::class);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        $tagTeam = $this->route()->parameter('tag_team');

        return [
            'name' => ['required', 'string', 'min:3', Rule::unique('tag_teams')->ignore($tagTeam->id)],
            'signature_move' => ['nullable', 'string'],
            'started_at' => ['nullable', 'string', 'date', new EmploymentStartDateCanBeChanged($tagTeam)],
            'wrestlers' => ['nullable', 'array'],
            'wrestlers.*', [
                'bail',
                'integer',
                'distinct',
                Rule::exists('wrestlers', 'id'),
                new WrestlerCanJoinExistingTagTeam($tagTeam),
            ],
        ];
    }
}
