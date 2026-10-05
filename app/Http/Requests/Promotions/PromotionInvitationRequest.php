<?php

declare(strict_types=1);

namespace App\Http\Requests\Promotions;

use App\Models\Users\User;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Accepting or declining an invitation takes no input: the promotion comes from the route and the invited
 * user is always the signed-in user.
 */
class PromotionInvitationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() instanceof User;
    }

    /** @return array<string, array<int, string>> */
    public function rules(): array
    {
        return [];
    }
}
