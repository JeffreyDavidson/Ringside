<?php

namespace Tests\Feature\Referees;

use App\Enums\Role;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Factories\RefereeFactory;
use Tests\TestCase;

/**
 * @group referees
 * @group roster
 */
class UpdateRefereeSuccessConditionsTest extends TestCase
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

    /**
     * @test
     * @dataProvider adminRoles
     */
    public function administrators_can_view_the_form_for_editing_a_referee($adminRoles)
    {
        $this->actAs($adminRoles);
        $referee = RefereeFactory::new()->create();

        $response = $this->editRequest($referee);

        $response->assertViewIs('referees.edit');
        $this->assertTrue($response->data('referee')->is($referee));
    }

    /**
     * @test
     * @dataProvider adminRoles
     */
    public function administrators_can_update_a_referee($adminRoles)
    {
        $this->actAs($adminRoles);
        $referee = RefereeFactory::new()->create();

        $response = $this->updateRequest($referee, $this->validParams());

        $response->assertRedirect(route('referees.index'));
        tap($referee->fresh(), function ($referee) {
            $this->assertEquals('John', $referee->first_name);
            $this->assertEquals('Smith', $referee->last_name);
        });
    }

    public function adminRoles()
    {
        return [
            [Role::ADMINISTRATOR],
            [Role::SUPER_ADMINISTRATOR],
        ];
    }
}
