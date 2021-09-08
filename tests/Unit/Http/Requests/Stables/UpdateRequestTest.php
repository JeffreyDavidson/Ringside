<?php

namespace Tests\Unit\Http\Requests\Stables;

use App\Http\Requests\Stables\UpdateRequest;
use App\Models\Stable;
use App\Rules\ActivationStartDateCanBeChanged;
use App\Rules\TagTeamCanJoinStable;
use App\Rules\WrestlerCanJoinStable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Routing\Route;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Exists;
use Illuminate\Validation\Rules\RequiredIf;
use Illuminate\Validation\Rules\Unique;
use Tests\TestCase;

/**
 * @group stables
 * @group roster
 * @group requests
 */
class UpdateRequestTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @test
     */
    public function rules_returns_validation_requirements()
    {
        $stable = Stable::factory()->create();

        $subject = $this->createFormRequest(UpdateRequest::class);
        $subject->setRouteResolver(function () use ($stable) {
            $stub = $this->createStub(Route::class);
            $stub->expects($this->any())->method('hasParameter')->with('stable')->willReturn(true);
            $stub->expects($this->any())->method('parameter')->with('stable')->willReturn($stable);

            return $stub;
        });

        $rules = $subject->rules();

        $this->assertValidationRules([
            'name' => ['required', 'string', 'min:3'],
            'started_at' => ['nullable', 'string', 'date'],
            'wrestlers' => ['array'],
            'wrestlers.*' => ['bail ', 'integer', 'distinct'],
            'tag_teams' => ['array'],
            'tag_teams.*' => ['bail', 'integer', 'distinct'],
        ], $rules);

        $this->assertValidationRuleContains($rules['name'], Unique::class);
        $this->assertValidationRuleContains($rules['started_at'], RequiredIf::class);
        $this->assertValidationRuleContains($rules['started_at'], ActivationStartDateCanBeChanged::class);
        $this->assertValidationRuleContains($rules['wrestlers.*'], Exists::class);
        $this->assertValidationRuleContains($rules['wrestlers.*'], WrestlerCanJoinStable::class);
        $this->assertValidationRuleContains($rules['tag_teams.*'], Exists::class);
        $this->assertValidationRuleContains($rules['tag_teams.*'], TagTeamCanJoinStable::class);
    }
}
