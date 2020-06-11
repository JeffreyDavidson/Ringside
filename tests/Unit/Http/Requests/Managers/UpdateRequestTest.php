<?php

namespace Tests\Unit\Http\Requests\Managers;

use App\Http\Requests\Managers\UpdateRequest;
use App\Rules\ConditionalEmploymentStartDateRule;
use JMac\Testing\Traits\AdditionalAssertions;
use Tests\TestCase;

/*
 * @group managers
 * @group roster
 */
class UpdateRequestTest extends TestCase
{
    use AdditionalAssertions;

    /** @var UpdateRequest */
    private $subject;

    public function setUp(): void
    {
        $this->subject = new UpdateRequest();
    }

    /** @test */
    public function rules_returns_validation_requirements()
    {
        $this->assertValidationRules([
            'first_name' => ['required', 'string', 'min:3'],
            'last_name' => ['required', 'string', 'min:3'],
        ], $this->subject->rules());

        // $this->assertValidationRuleContains('started_at', ConditionalEmploymentStartDateRule::class);
    }
}
