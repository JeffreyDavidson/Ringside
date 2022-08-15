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

    /** @var class-string */
    public static $factory = TagTeamRequestFactory::class;

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

        if (! $this->route()->hasParameter('tag_team') || is_null($this->route()->parameter('tag_team'))) {
            return false;
        }

        return $this->user()->can('update', TagTeam::class);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        /** @var \App\Models\TagTeam */
        $tagTeam = $this->route()->parameter('tag_team');

        return [
            'name' => ['required', 'string', 'min:3', Rule::unique('tag_teams')->ignore($tagTeam->id)],
            'signature_move' => ['nullable', 'string'],
            'start_date' => ['nullable', 'string', 'date', new EmploymentStartDateCanBeChanged($tagTeam)],
            'wrestlerA' => ['integer', 'different:wrestlerB', Rule::exists('wrestlers', 'id'), new WrestlerCanJoinExistingTagTeam($tagTeam)],
            'wrestlerB' => ['integer', 'different:wrestlerA', Rule::exists('wrestlers', 'id'), new WrestlerCanJoinExistingTagTeam($tagTeam)],
        ];
    }
}
