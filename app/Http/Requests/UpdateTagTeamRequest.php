<?php

namespace App\Http\Requests;

use App\Models\TagTeam;
use App\Rules\CanJoinTagTeam;
use Illuminate\Foundation\Http\FormRequest;

class UpdateTagTeamRequest extends FormRequest
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
            'name' => ['required'],
            'signature_move' => ['nullable'],
            'hired_at' => ['required', 'date_format:Y-m-d H:i:s'],
            'wrestlers' => ['required', 'array', 'size:2'],
            'wrestlers.*' => ['bail', 'integer', 'exists:wrestlers,id', new CanJoinTagTeam],
        ];
    }
}
