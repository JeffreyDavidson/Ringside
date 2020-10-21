<?php

namespace Tests\Unit\Http\Requests\Referees;

use App\Http\Requests\Referees\UpdateRequest;
use App\Models\Referee;
use App\Rules\EmploymentStartDateCanBeChanged;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Routing\Route;
use Tests\TestCase;

/**
 * @group referees
 * @group roster
 * @group requests
 */
class UpdateRequestTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function rules_returns_validation_requirements()
    {
        $referee = Referee::factory()->create();

        $subject = $this->createFormRequest(UpdateRequest::class);
        $subject->setRouteResolver(function () use ($referee) {
            $stub = $this->createStub(Route::class);
            $stub->expects($this->any())->method('hasParameter')->with('referee')->willReturn(true);
            $stub->expects($this->any())->method('parameter')->with('referee')->willReturn($referee);

            return $stub;
        });

        $rules = $subject->rules();

        $this->assertValidationRules(
            [
                'first_name' => ['required', 'string'],
                'last_name' => ['required', 'string'],
                'started_at' => ['nullable', 'string', 'date_format:Y-m-d H:i:s'],
            ],
            $rules
        );

        $this->assertValidationRuleContains($rules['started_at'], EmploymentStartDateCanBeChanged::class);
    }
}
