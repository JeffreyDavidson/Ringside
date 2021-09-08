<?php

namespace Tests\Unit\Http\Requests\Titles;

use App\Http\Requests\Titles\UpdateRequest;
use App\Models\Title;
use App\Rules\ActivationStartDateCanBeChanged;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Routing\Route;
use Illuminate\Validation\Rules\Unique;
use Tests\TestCase;

/**
 * @group titles
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
        $title = Title::factory()->create();

        $subject = $this->createFormRequest(UpdateRequest::class);
        $subject->setRouteResolver(function () use ($title) {
            $stub = $this->createStub(Route::class);
            $stub->expects($this->any())->method('hasParameter')->with('title')->willReturn(true);
            $stub->expects($this->any())->method('parameter')->with('title')->willReturn($title);

            return $stub;
        });

        $rules = $subject->rules();

        $this->assertValidationRules(
            [
                'name' => ['required', 'string', 'min:3', 'ends_with:Title,Titles'],
                'activated_at' => ['nullable', 'string', 'date'],
            ],
            $rules
        );

        $this->assertValidationRuleContains($rules['name'], Unique::class);
        $this->assertValidationRuleContains($rules['activated_at'], ActivationStartDateCanBeChanged::class);
    }
}
