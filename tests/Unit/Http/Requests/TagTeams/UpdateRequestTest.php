<?php

namespace Tests\Unit\Http\Requests\TagTeams;

use App\Http\Requests\TagTeams\UpdateRequest;
use App\Models\TagTeam;
use App\Rules\CannotBelongToMultipleEmployedTagTeams;
use App\Rules\EmploymentStartDateCanBeChanged;
use Illuminate\Routing\Route;
use Illuminate\Validation\Rules\Unique;
use Tests\TestCase;

/**
 * @group tagteams
 * @group roster
 * @group requests
 */
class UpdateRequestTest extends TestCase
{
    /**
     * @test
     */
    public function rules_returns_validation_requirements()
    {
        $this->markTestIncomplete();
        $tagTeamMock = $this->mock(TagTeam::class);

        $subject = $this->createFormRequest(UpdateRequest::class);
        $subject->setRouteResolver(function () use ($tagTeamMock) {
            $stub = $this->createStub(Route::class);
            $tagTeamMock->expects()->getAttribute('id')->andReturns(\Mockery::any());
            $stub->expects($this->any())->method('parameter')->with('tag_team')->willReturn($tagTeamMock);

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
