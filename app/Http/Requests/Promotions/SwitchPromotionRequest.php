<?php

declare(strict_types=1);

namespace App\Http\Requests\Promotions;

use App\Models\Users\User;
use Illuminate\Foundation\Http\FormRequest;

class SwitchPromotionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() instanceof User;
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'promotion_id' => [
                'required',
                'integer',
                'exists:promotions,id',
            ],
        ];
    }
}
