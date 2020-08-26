<?php

namespace Tests\Unit\Http\Requests\TagTeams;

use App\Http\Requests\TagTeams\StoreRequest;
use App\Rules\WrestlerCanJoinTagTeamRule;
use Illuminate\Validation\Rules\Exists;
use Illuminate\Validation\Rules\Unique;
use Tests\TestCase;

/**
 * @group tagteams
 * @group roster
 * @group requests
 */
class StoreRequestTest extends TestCase
{
    /** @test */
    public function authorize_returns_false_when_unauthenticated()
    {
        $subject = new StoreRequest();

        $this->assertFalse($subject->authorize());
    }

    /** @test */
    public function rules_returns_validation_requirements()
    {
        $subject = $this->createFormRequest(StoreRequest::class);
        $rules = $subject->rules();

        $this->assertValidationRules([
            'name' => ['required', 'string'],
            'signature_move' => ['nullable', 'string'],
            'started_at' => ['nullable', 'string', 'date_format:Y-m-d H:i:s'],
            'wrestler1' => ['nullable', 'bail', 'integer', 'different:wrestler2'],
            'wrestler2' => ['nullable', 'bail', 'integer', 'different:wrestler1'],
        ], $rules);

        $this->assertValidationRuleContains($rules['name'], Unique::class);
        $this->assertValidationRuleContains($rules['wrestler1'], Exists::class);
        $this->assertValidationRuleContains($rules['wrestler1'], WrestlerCanJoinTagTeamRule::class);
        $this->assertValidationRuleContains($rules['wrestler2'], Exists::class);
        $this->assertValidationRuleContains($rules['wrestler2'], WrestlerCanJoinTagTeamRule::class);
    }
}
