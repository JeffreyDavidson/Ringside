<?php

namespace Tests\Unit\Http\Requests\TagTeams;

use App\Http\Requests\TagTeams\UpdateRequest;
use App\Rules\WrestlerCanJoinTagTeamRule;
use Illuminate\Validation\Rules\Unique;
use Tests\TestCase;

/**
 * @group tagteams
 * @group roster
 * @group requests
 */
class UpdateRequestTest extends TestCase
{
    /** @test */
    public function authorized_returns_false_when_unauthenticated()
    {
        $subject = new UpdateRequest();

        $this->assertFalse($subject->authorize());
    }

    /** @test */
    public function rules_returns_validation_requirements()
    {
        $this->markTestIncomplete('Needs a route paramter set.');
        $subject = $this->createFormRequest(UpdateRequest::class);
        $rules = $subject->rules();

        $this->assertValidationRules([
            'name' => ['required', 'string'],
            'signature_move' => ['nullable', 'string'],
        ], $rules);

        $this->assertValidationRuleContains($rules['name'], Unique::class);
        $this->assertValidationRuleContains($rules['started_at'], ConditionalEmploymentStartDateRule::class);
        $this->assertValidationRuleContains($rules['wrestler1'], WrestlerCanJoinTagTeamRule::class);
        $this->assertValidationRuleContains($rules['wrestler2'], WrestlerCanJoinTagTeamRule::class);
    }
}
