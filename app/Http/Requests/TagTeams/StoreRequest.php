<?php

declare(strict_types=1);

namespace App\Http\Requests\TagTeams;

use App\Models\TagTeam;
use App\Rules\WrestlerCanJoinNewTagTeam;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Tests\RequestFactories\TagTeamRequestFactory;
use Worksome\RequestFactories\Concerns\HasFactory;

class StoreRequest extends FormRequest
{
    use HasFactory;

    /** @var class-string */
    public static $factory = TagTeamRequestFactory::class;

    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        if (is_null($this->user())) {
            return false;
        }

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
            'start_date' => ['nullable', 'string', 'date'],
            'wrestlerA' => ['integer', 'different:wrestlerB', Rule::exists('wrestlers', 'id'), new WrestlerCanJoinNewTagTeam()],
            'wrestlerB' => ['integer', 'different:wrestlerA', Rule::exists('wrestlers', 'id'), new WrestlerCanJoinNewTagTeam()],
        ];
    }
}
