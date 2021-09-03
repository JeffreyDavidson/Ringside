<?php

namespace Tests\Unit\Http\Requests\Managers;

use App\Http\Requests\Managers\UpdateRequest;
use App\Models\Manager;
use App\Rules\EmploymentStartDateCanBeChanged;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Routing\Route;
use Tests\TestCase;

/**
 * @group managers
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
        $this->markTestIncomplete();
        $manager = Manager::factory()->create();

        $subject = $this->createFormRequest(UpdateRequest::class);
        $subject->setRouteResolver(function () use ($manager) {
            $stub = $this->createStub(Route::class);
            $stub->expects($this->any())->method('hasParameter')->with('manager')->willReturn(true);
            $stub->expects($this->any())->method('parameter')->with('manager')->willReturn($manager);

            return $stub;
        });

        $rules = $subject->rules();

        $this->assertValidationRules(
            [
                'first_name' => ['required', 'string', 'min:3'],
                'last_name' => ['required', 'string', 'min:3'],
                'started_at' => ['nullable', 'string', 'date_format:Y-m-d H:i:s'],
            ],
            $rules
        );

        $this->assertValidationRuleContains($rules['started_at'], EmploymentStartDateCanBeChanged::class);
    }
}
