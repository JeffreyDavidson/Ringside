<?php

namespace App\Http\Requests;

use App\Enums\WrestlerStatus;

class IndexWrestlerRequest extends AjaxOnlyFormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'status' => [
                'nullable',
                'string',
                WrestlerStatus::rule()
            ],
            'started_at' => [
                'nullable',
                'array',
            ],
            'started_at.0' => [
                'nullable',
                'string',
                'date_format:Y-m-d H:i:s'
            ],
            'started_at.1' => [
                'nullable',
                'required_with:started_at.0',
                'string',
                'date_format:Y-m-d H:i:s',
                'after:started_at.0'
            ],
        ];
    }
}
