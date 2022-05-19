<?php

declare(strict_types=1);

namespace App\Http\Requests\TagTeams;

use App\Models\TagTeam;
use App\Models\Wrestler;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return $this->user()->can('create', TagTeam::class);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'name' => ['required', 'string', 'min:3', Rule::unique('tag_teams', 'name')],
            'signature_move' => ['nullable', 'string'],
            'started_at' => ['nullable', 'string', 'date'],
            'wrestlers' => ['nullable', 'array', 'required_with:signature_move'],
            'wrestlers.*' => [
                'nullable',
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
                $this->collect('wrestlers')->each(function ($wrestlerId, $key) use ($validator) {
                    $wrestler = Wrestler::query()
                        ->with(['currentEmployment', 'futureEmployment'])
                        ->whereKey($wrestlerId)
                        ->sole();

                    if ($wrestler->isSuspended()) {
                        $validator->errors()->add(
                            'wrestlers',
                            "{$wrestler->name} is suspended and cannot join a tag team."
                        );

                        $validator->addFailure(
                            'wrestlers.'.$key,
                            'cannot_be_suspended_to_join_tag_team'
                        );
                    }

                    if ($wrestler->isInjured()) {
                        $validator->errors()->add(
                            'wrestlers',
                            "{$wrestler->name} is injured and cannot join a tag team."
                        );

                        $validator->addFailure(
                            'wrestlers.'.$key,
                            'cannot_be_injured_to_join_tag_team'
                        );
                    }

                    if ($wrestler->currentTagTeam() !== null && $wrestler->currentTagTeam()->exists()) {
                        $validator->errors()->add(
                            'wrestlers',
                            "{$wrestler->name} is already a part of a bookable tag team."
                        );

                        $validator->addFailure(
                            'wrestlers.'.$key,
                            'cannot_belong_to_multiple_employed_tag_teams'
                        );
                    }
                });
            }
        });
    }
}
