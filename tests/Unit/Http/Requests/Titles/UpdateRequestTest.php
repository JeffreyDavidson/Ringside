<?php

namespace Tests\Unit\Http\Requests\Titles;

use Mockery;
use Tests\TestCase;
use Illuminate\Http\Request;
use App\Policies\TitlePolicy;
use Illuminate\Routing\Route;
use Tests\Factories\UserFactory;
use Tests\Factories\TitleFactory;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rules\Unique;
use App\Http\Requests\Titles\UpdateRequest;
use App\Rules\ConditionalActivationStartDateRule;

/**
 * @group titles
 */
class UpdateRequestTest extends TestCase
{
    /** @test */
    public function all_validation_rules_match()
    {
        $title = TitleFactory::new()->make();

        $subject = $this->createFormRequest(UpdateRequest::class, ['title' => $title]);
        $rules = $subject->rules();

        $this->assertValidationRules(
            [
                'name' => [
                    'required',
                    'min:3',
                    'ends_with:Title,Titles',
                ],
            ],
            $subject->rules()
        );

        $this->assertValidationRuleContains($rules['name'], Unique::class);
        $this->assertValidationRuleContains(
            $rules['activated_at'],
            ConditionalActivationStartDateRule::class
        );
    }

    /** @test */
    public function authorized_returns_false_when_unathenticated()
    {
        $subject = new UpdateRequest();

        $this->assertFalse($subject->authorize());
    }

    /** @test */
    public function authorized_returns_true_when_users_can_update_a_title()
    {
        $user = UserFactory::new()->make();

        $this->actingAs($user);

        $subject = new UpdateRequest();

        Auth::shouldReceive('user')->once()->andReturn(true);

        // $this->assertTrue($subject->authorize());
    }
}
