<?php

namespace Tests\Unit\Http\Requests\Stables;

use App\Http\Requests\Stables\UpdateRequest;
use App\Rules\ActivationStartDateCanBeChanged;
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
class UpdateRequestTest extends TestCase
{
    /** @test */
    public function rules_returns_validation_requirements()
    {
        $subject = $this->createFormRequest(UpdateRequest::class);
        $rules = $subject->rules();

        $this->assertValidationRules([
            'name' => ['filled'],
            'started_at' => ['sometimes', 'string', 'date_format:Y-m-d H:i:s'],
            'wrestlers' => ['array'],
            'wrestlers.*' => ['bail ', 'integer'],
            'tag_teams' => ['array'],
            'tag_teams.*' => ['bail', 'integer'],
        ], $rules);

        $this->assertValidationRuleContains($rules['name'], Unique::class);
        // $this->assertValidationRuleContains($rules['started_at'], ActivationStartDateCanBeChanged::class);
        $this->assertValidationRuleContains($rules['wrestlers.*'], Exists::class);
        // $this->assertValidationRuleContains($rules['wrestlers.*'], WrestlerCanJoinStable::class);
        $this->assertValidationRuleContains($rules['tag_teams.*'], Exists::class);
        // $this->assertValidationRuleContains($rules['tag_teams.*'], TagTeamCanJoinStable::class);
    }
}
