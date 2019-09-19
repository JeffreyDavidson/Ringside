<?php

namespace Tests\Feature\Admin\Wrestlers;

use Tests\TestCase;
use Carbon\Carbon;
use App\Models\Wrestler;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * @group wrestlers
 * @group admins
 * @group roster
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
            'started_at' => now()->toDateTimeString(),
        ], $overrides);
    }

    /** @test */
    public function an_administrator_can_view_the_form_for_creating_a_wrestler()
    {
        $this->actAs('administrator');

        $response = $this->createRequest('wrestler');

        $response->assertViewIs('wrestlers.create');
        $response->assertViewHas('wrestler', new Wrestler);
    }

    /** @test */
    public function an_administrator_can_create_a_wrestler()
    {
        $now = now();
        Carbon::setTestNow($now);

        $this->actAs('administrator');

        $response = $this->storeRequest('wrestler', $this->validParams());

        $response->assertRedirect(route('wrestlers.index'));
        tap(Wrestler::first(), function ($wrestler) use ($now) {
            $this->assertEquals('Example Wrestler Name', $wrestler->name);
            $this->assertEquals(76, $wrestler->height);
            $this->assertEquals(240, $wrestler->weight);
            $this->assertEquals('Laraville, FL', $wrestler->hometown);
            $this->assertEquals('The Finisher', $wrestler->signature_move);
            $this->assertEquals($now->toDateTimeString(), $wrestler->employment->started_at);
        });
    }
}
