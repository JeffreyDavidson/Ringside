<?php

declare(strict_types=1);

namespace App\Http\Requests\Auth;

use App\Http\Requests\Concerns\NormalizesEmail;
use App\Rules\Users\UniqueEmail;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules;

class RegisterUserRequest extends FormRequest
{
    use NormalizesEmail;

    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, string|Rules\Password|UniqueEmail>>
     */
    public function rules(): array
    {
        return [
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', new UniqueEmail],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ];
    }
}
