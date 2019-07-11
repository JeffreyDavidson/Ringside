<?php

namespace Tests\Feature\SuperAdmin\Wrestlers;

use Tests\TestCase;
use App\Models\User;
use App\Models\Wrestler;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * @group wrestlers
 * @group superadmins
 */
class UpdateWrestlerSuccessConditionsTest extends TestCase
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
    public function a_super_administrator_can_view_the_form_for_editing_a_wrestler()
    {
        $user = factory(User::class)->states('super-administrator')->create();
        $wrestler = factory(Wrestler::class)->create($this->oldAttributes());

        $response = $this->actingAs($user)
                        ->get(route('wrestlers.edit', $wrestler));

        $response->assertViewIs('wrestlers.edit');
        $this->assertTrue($response->data('wrestler')->is($wrestler));
    }

    /** @test */
    public function a_super_administrator_can_update_a_wrestler()
    {
        $user = factory(User::class)->states('super-administrator')->create();
        $wrestler = factory(Wrestler::class)->create($this->oldAttributes());

        $response = $this->actingAs($user)
                        ->from(route('wrestlers.edit', $wrestler))
                        ->patch(route('wrestlers.update', $wrestler), $this->validParams());

        $response->assertRedirect(route('wrestlers.index'));
        tap($wrestler->fresh(), function ($wrestler) {
            $this->assertEquals('Example Wrestler Name', $wrestler->name);
            $this->assertEquals(76, $wrestler->height);
            $this->assertEquals(240, $wrestler->weight);
            $this->assertEquals('Laraville, FL', $wrestler->hometown);
            $this->assertEquals('The Finisher', $wrestler->signature_move);
        });
    }

    /** @test */
    public function a_wrestler_signature_move_is_optional()
    {
        $user = factory(User::class)->states('super-administrator')->create();
        $wrestler = factory(Wrestler::class)->create($this->oldAttributes());

        $response = $this->actingAs($user)
                        ->from(route('wrestlers.edit', $wrestler))
                        ->patch(route('wrestlers.update', $wrestler), $this->validParams([
                            'signature_move' => '',
                        ]));

        $response->assertSessionDoesntHaveErrors('signature_move');
        $response->assertRedirect(route('wrestlers.index'));
        tap($wrestler->fresh(), function ($wrestler) {
            $this->assertNull($wrestler->signature_move);
        });
    }
}
