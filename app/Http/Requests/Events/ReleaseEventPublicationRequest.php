<?php

declare(strict_types=1);

namespace App\Http\Requests\Events;

use App\Enums\Events\PublicSection;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ReleaseEventPublicationRequest extends FormRequest
{
    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'version' => ['required', 'integer', 'min:1'],
            'fingerprint' => ['required', 'string', 'size:64'],
            'section' => ['required', Rule::enum(PublicSection::class)],
            'reviewed' => $this->isMethod('delete')
                ? ['nullable', 'boolean']
                : ['required', 'accepted'],
        ];
    }
}
