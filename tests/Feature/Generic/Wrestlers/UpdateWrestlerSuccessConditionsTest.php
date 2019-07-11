<?php

namespace Tests\Feature\Generic\Wrestlers;

use Tests\TestCase;
use App\Models\Wrestler;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * @group wrestlers
 * @group generics
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
    public function a_wrestler_signature_move_is_optional()
    {
        $this->actAs('administrator');
        $wrestler = factory(Wrestler::class)->create($this->oldAttributes());

        $response = $this->from(route('wrestlers.edit', $wrestler))
                        ->patch(route('wrestlers.update', $wrestler), $this->validParams([
                            'signature_move' => '',
                        ]));

        $response->assertSessionDoesntHaveErrors('signature_move');
        $response->assertRedirect(route('wrestlers.index'));
        tap($wrestler->first(), function ($wrestler) {
            $this->assertNull($wrestler->signature_move);
        });
    }
}
