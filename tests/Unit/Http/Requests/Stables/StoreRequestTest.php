<?php

namespace Tests\Unit\Http\Requests\Stables;

use App\Http\Requests\Stables\StoreRequest;
use App\Rules\TagTeamCanJoinStable;
use App\Rules\WrestlerCanJoinStable;
use Illuminate\Validation\Rules\Exists;
use Illuminate\Validation\Rules\RequiredIf;
use Illuminate\Validation\Rules\Unique;
use Tests\TestCase;

/**
 * @group stables
 * @group roster
 * @group requests
 */
class StoreRequestTest extends TestCase
{
    /** @test */
    public function rules_returns_validation_requirements()
    {
        $subject = $this->createFormRequest(StoreRequest::class);
        $rules = $subject->rules();

        $this->assertValidationRules(
            [
                'name' => ['required', 'string'],
                'started_at' => ['nullable', 'string', 'date_format:Y-m-d H:i:s'],
                'wrestlers' => ['array'],
                'tag_teams' => ['array'],
                'wrestlers.*' => ['bail', 'integer'],
                'tag_teams.*' => ['bail', 'integer'],
            ],
            $rules
        );

        $this->assertValidationRuleContains($rules['name'], Unique::class);
        $this->assertValidationRuleContains($rules['wrestlers'], RequiredIf::class);
        $this->assertValidationRuleContains($rules['tag_teams'], RequiredIf::class);
        $this->assertValidationRuleContains($rules['wrestlers.*'], Exists::class);
        // $this->assertValidationRuleContains($rules['wrestlers.*'], WrestlerCanJoinStable::class);
        $this->assertValidationRuleContains($rules['tag_teams.*'], Exists::class);
        // $this->assertValidationRuleContains($rules['tag_teams.*'], TagTeamCanJoinStable::class);
    }
}
