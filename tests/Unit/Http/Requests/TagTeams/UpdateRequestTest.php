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
    public function rules_returns_validation_requirements()
    {
        $subject = $this->createFormRequest(UpdateRequest::class);
        $rules = $subject->rules();

        $this->assertValidationRules([
            'name' => ['required', 'string'],
            'signature_move' => ['nullable', 'string'],
            'started_at' => ['nullable', 'string', 'date_format:Y-m-d H:i:s'],
            'wrestlers' => ['nullable', 'array'],
        ], $rules);

        $this->assertValidationRuleContains($rules['name'], Unique::class);
        // $this->assertValidationRuleContains($rules['started_at'], EmploymentStartDateCanBeChanged::class);
        // $this->assertValidationRuleContains($rules['wrestlers.*'], WrestlerCanJoinTagTeamRule::class);
    }
}
