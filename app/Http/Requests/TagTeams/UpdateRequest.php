<?php

namespace App\Http\Requests\TagTeams;

use App\Models\TagTeam;
use App\Rules\CannotBeEmployedAfterDate;
use App\Rules\CannotBeHindered;
use App\Rules\CannotBelongToMultipleEmployedTagTeams;
use App\Rules\EmploymentStartDateCanBeChanged;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateRequest extends FormRequest
{
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
        return [
            'name' => ['required', 'string', 'min:3', Rule::unique('tag_teams')->ignore($this->route('tag_team')->id)],
            'signature_move' => ['nullable', 'string'],
            'started_at' => [
                'nullable',
                'string',
                'date',
                new EmploymentStartDateCanBeChanged($this->route('tag_team')),
            ],
            'wrestlers' => ['nullable', 'array'],
            'wrestlers.*', [
                'bail',
                'integer',
                'distinct',
                Rule::exists('wrestlers', 'id'),
                new CannotBeEmployedAfterDate($this->input('started_at')),
                new CannotBeHindered,
                new CannotBelongToMultipleEmployedTagTeams($this->route('tag_team')),
            ],
        ];
    }
}
