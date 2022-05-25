<?php

declare(strict_types=1);

namespace App\Http\Requests\Titles;

use App\Models\Title;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Tests\RequestFactories\TitleRequestFactory;
use Worksome\RequestFactories\Concerns\HasFactory;

class StoreRequest extends FormRequest
{
    use HasFactory;

    public static $factory = TitleRequestFactory::class;

    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return $this->user()->can('create', Title::class);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'name' => ['required', 'string', 'min:3', 'ends_with:Title,Titles', Rule::unique('titles', 'name')],
            'activated_at' => ['nullable', 'string', 'date'],
        ];
    }
}
