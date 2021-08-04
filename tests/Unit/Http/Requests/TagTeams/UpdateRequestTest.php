<?php

namespace Tests\Unit\Http\Requests\TagTeams;

use Tests\TestCase;
use App\Models\TagTeam;
use Illuminate\Routing\Route;
use Illuminate\Validation\Rules\Unique;
use App\Http\Requests\TagTeams\UpdateRequest;
use App\Rules\EmploymentStartDateCanBeChanged;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Rules\CannotBelongToMultipleEmployedTagTeams;

/**
 * @group tagteams
 * @group roster
 * @group requests
 */
class UpdateRequestTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function rules_returns_validation_requirements()
    {
        $tagTeam = TagTeam::factory()->create();

        $subject = $this->createFormRequest(UpdateRequest::class);
        $subject->setRouteResolver(function () use ($tagTeam) {
            $stub = $this->createStub(Route::class);
            // $stub->expects($this->any())->method('hasParameter')->with('tag_team')->willReturn(true);
            $stub->expects($this->any())->method('parameter')->with('tag_team')->willReturn($tagTeam);

            return $stub;
        });

        $rules = $subject->rules();

        $this->assertValidationRules([
            'name' => ['required', 'string'],
            'signature_move' => ['nullable', 'string'],
            'started_at' => ['nullable', 'string', 'date_format:Y-m-d H:i:s'],
            'wrestlers' => ['array'],
            'wrestlers.*' => ['bail', 'integer', 'distinct'],
        ], $rules);

        $this->assertValidationRuleContains($rules['name'], Unique::class);
        $this->assertValidationRuleContains($rules['started_at'], EmploymentStartDateCanBeChanged::class);
        $this->assertValidationRuleContains($rules['wrestlers.*'], Exists::class);
        $this->assertValidationRuleContains($rules['wrestlers.*'], CannotBeEmployedAfterDate::class);
        $this->assertValidationRuleContains($rules['wrestlers.*'], CannotBeHindered::class);
        $this->assertValidationRuleContains($rules['wrestlers.*'], CannotBelongToMultipleEmployedTagTeams::class);
    }
}
