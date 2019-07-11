<?php

namespace Tests\Feature\Wrestlers;

use Tests\TestCase;
use App\Models\User;
use App\Models\Wrestler;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * @group wrestlers
 * @group users
 */
class UpdateWrestlerFailureConditionsTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Default attributes for model.
     *
     * @param  array  $overrides
     * @return array
     */
    private function oldAttributes($overrides = [])
    {
        return array_replace([
            'name' => 'Old Wrestler Name',
            'height' => 73,
            'weight' => 240,
            'hometown' => 'Old City, State',
            'signature_move' => 'Old Finisher',
            'hired_at' => today()->toDateString(),
        ], $overrides);
    }

    /**
     * Valid parameters for request.
     *
     * @param  array $overrides
     * @return array
     */
    private function validParams($overrides = [])
    {
        return array_replace([
            'name' => 'Example Wrestler Name',
            'feet' => '6',
            'inches' => '4',
            'weight' => '240',
            'hometown' => 'Laraville, FL',
            'signature_move' => 'The Finisher',
            'hired_at' => today()->toDateString(),
        ], $overrides);
    }

    /** @test */
    public function a_basic_user_cannot_view_the_form_for_editing_a_wrestler()
    {
        $user = factory(User::class)->states('basic-user')->create();
        $wrestler = factory(Wrestler::class)->create($this->oldAttributes());

        $response = $this->actingAs($user)
                        ->get(route('wrestlers.edit', $wrestler));

        $response->assertForbidden();
    }

    /** @test */
    public function a_basic_user_cannot_update_a_wrestler()
    {
        $user = factory(User::class)->states('basic-user')->create();
        $wrestler = factory(Wrestler::class)->create($this->oldAttributes());

        $response = $this->actingAs($user)
                        ->patch(route('wrestlers.update', $wrestler), $this->validParams());

        $response->assertForbidden();
    }
}
