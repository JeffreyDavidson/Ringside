<?php

namespace App\Http\Requests\TagTeams;

use App\Models\TagTeam;
use App\Models\Wrestler;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

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
            'name' => [
                'required',
                'string',
                'min:3',
                Rule::unique('tag_teams')->ignore($this->route()->parameter('tag_team')->id),
            ],
            'signature_move' => ['nullable', 'string'],
            'started_at' => [
                'nullable',
                'string',
                'date',
            ],
            'wrestlers' => ['nullable', 'array'],
            'wrestlers.*', [
                'bail',
                'integer',
                'distinct',
                Rule::exists('wrestlers', 'id'),
            ],
        ];
    }

    /**
     * Configure the validator instance.
     *
     * @param  \Illuminate\Validation\Validator  $validator
     * @return void
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            if ($validator->errors()->isEmpty()) {
                $tagTeam = $this->route()->parameter('tag_team');

                if ($tagTeam->isCurrentlyEmployed() && ! $tagTeam->employedOn($this->date('started_at'))) {
                    $validator->errors()->add(
                        'started_at',
                        "{$tagTeam->name} is currently employed and the employment date cannot be changed."
                    );

                    $validator->addFailure('started_at', 'employment_date_cannot_be_changed');
                }

                if ($this->collect('wrestlers')->isNotEmpty()) {
                    $this->collect('wrestlers')->each(function ($wrestlerId) use ($validator) {
                        $wrestler = Wrestler::whereKey($wrestlerId)->sole();

                        if ($wrestler->isCurrentlyEmployed() && $wrestler->employedAfter($this->date('started_at'))) {
                            $validator->errors()->add(
                                'wrestlers',
                                "{$wrestler->name} is currently employed and tag team employment start date has past."
                            );
                        }

                        if ($wrestler->currentTagTeam() !== null
                            && ! $wrestler->currentTagTeam()->isNot($this->route()->parameter('tag_team'))
                        ) {
                            $validator->errors()->add(
                                'wrestlers',
                                "{$wrestler->name} is already a member of a tag team."
                            );
                        }
                    });
                }
            }
        });
    }
}
