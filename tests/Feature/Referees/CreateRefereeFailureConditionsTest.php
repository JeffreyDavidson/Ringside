<?php

namespace Tests\Feature\Referees;

use App\Enums\Role;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use TRegx\DataProvider\DataProviders;

/**
 * @group referees
 * @group roster
 */
class CreateRefereeFailureConditionsTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Valid parameters for request.
     *
     * @param  array $overrides
     * @return array
     */
    private function validParams($overrides = [])
    {
        return array_replace([
            'first_name' => 'John',
            'last_name' => 'Smith',
            'started_at' => now()->toDateTimeString(),
        ], $overrides);
    }

    /** @test */
    public function a_basic_user_cannot_view_the_form_for_creating_a_referee()
    {
        $this->actAs(Role::BASIC);

        $response = $this->createRequest('referee');

        $response->assertForbidden();
    }

    /** @test */
    public function a_basic_user_cannot_create_a_referee()
    {
        $this->actAs(Role::BASIC);

        $response = $this->storeRequest('referees', $this->validParams());

        $response->assertForbidden();
    }

    /** @test */
    public function a_guest_cannot_view_the_form_for_creating_a_referee()
    {
        $response = $this->createRequest('referee');

        $response->assertRedirect(route('login'));
    }

    /** @test */
    public function a_guest_cannot_create_a_referee()
    {
        $response = $this->storeRequest('referee', $this->validParams());

        $response->assertRedirect(route('login'));
    }

    /**
     * @test
     * @dataProvider providers
     */
    public function test_form_validation($adminUsers, $formInput, $formInputValue)
    {
        $this->actAs($adminUsers);

        $response = $this->storeRequest('referee', $this->validParams([
            $formInput => $formInputValue,
        ]));

        $response->assertStatus(302);
        $response->assertRedirect(route('referees.create'));
        $response->assertSessionHasErrors($formInput);
    }

    public function providers()
    {
        return DataProviders::cross(
            $this->adminRoles(),
            $this->formValidation()
        );
    }

    public function adminRoles()
    {
        return [
            [Role::ADMINISTRATOR],
            [Role::SUPER_ADMINISTRATOR],
        ];
    }

    public function formValidation()
    {
        return [
            ['first_name', ''],
            ['first_name', ['not-a-string']],
            ['last_name', ''],
            ['last_name', ['not-a-string']],
            ['started_at', ['not-a-string']],
            ['started_at', now()->toDateString()],
        ];
    }
}
