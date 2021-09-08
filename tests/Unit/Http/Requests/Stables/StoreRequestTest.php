<?php

namespace Tests\Unit\Http\Requests\Stables;

use App\Http\Requests\Stables\StoreRequest;
use App\Rules\StableHasEnoughMembers;
use App\Rules\TagTeamCanJoinStable;
use App\Rules\WrestlerCanJoinStable;
use Illuminate\Validation\Rules\Exists;
use Illuminate\Validation\Rules\Unique;
use Tests\TestCase;

/**
 * @group stables
 * @group roster
 * @group requests
 */
class StoreRequestTest extends TestCase
{
    /**
     * @test
     */
    public function rules_returns_validation_requirements()
    {
        $subject = $this->createFormRequest(StoreRequest::class);
        $rules = $subject->rules();

        $this->assertValidationRules(
            [
                'name' => ['required', 'string', 'min:3'],
                'started_at' => ['nullable', 'string', 'date'],
                'wrestlers' => ['array'],
                'tag_teams' => ['array'],
                'wrestlers.*' => ['bail', 'integer'],
                'tag_teams.*' => ['bail', 'integer'],
            ],
            $rules
        );

        $this->assertValidationRuleContains($rules['name'], Unique::class);
        $this->assertValidationRuleContains($rules['wrestlers.*'], Exists::class);
        $this->assertValidationRuleContains($rules['wrestlers.*'], WrestlerCanJoinStable::class);
        $this->assertValidationRuleContains($rules['tag_teams.*'], Exists::class);
        $this->assertValidationRuleContains($rules['tag_teams.*'], TagTeamCanJoinStable::class);
    }
}
