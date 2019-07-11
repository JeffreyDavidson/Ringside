<?php

namespace Tests\Feature\Generic\Wrestlers;

use Carbon\Carbon;
use Tests\TestCase;
use App\Models\Wrestler;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * @group wrestlers
 * @group generics
 */
class CreateWrestlerSuccessConditionsTest extends TestCase
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
            'name' => 'Example Wrestler Name',
            'feet' => '6',
            'inches' => '4',
            'weight' => '240',
            'hometown' => 'Laraville, FL',
            'signature_move' => 'The Finisher',
            'hired_at' => now()->toDateTimeString(),
        ], $overrides);
    }

    /** @test */
    public function a_wrestler_hired_today_or_before_is_bookable()
    {
        $this->actAs('administrator');

        $this->from(route('wrestlers.create'))
            ->post(route('wrestlers.store'), $this->validParams([
                'hired_at' => today()->toDateTimeString()
            ]));

        tap(Wrestler::first(), function ($wrestler) {
            $this->assertTrue($wrestler->is_bookable);
        });
    }

    /** @test */
    public function a_wrestler_hired_after_today_is_not_bookable()
    {
        $this->actAs('administrator');

        $this->from(route('wrestlers.create'))
            ->post(route('wrestlers.store'), $this->validParams([
                'hired_at' => Carbon::tomorrow()->toDateTimeString()
            ]));

        tap(Wrestler::first(), function ($wrestler) {
            $this->assertFalse($wrestler->is_bookable);
            $this->assertFalse($wrestler->is_hired);
        });
    }

    /** @test */
    public function a_wrestler_signature_move_is_optional()
    {
        $this->actAs('administrator');

        $response = $this->from(route('wrestlers.create'))
                        ->post(route('wrestlers.store'), $this->validParams([
                            'signature_move' => '',
                        ]));

        $response->assertSessionDoesntHaveErrors('signature_move');
        $response->assertRedirect(route('wrestlers.index'));
        tap(Wrestler::first(), function ($wrestler) {
            $this->assertNull($wrestler->signature_move);
        });
    }
}
