<?php

namespace Tests\Feature\Admin\Wrestlers;

use Carbon\Carbon;
use Tests\TestCase;
use App\Models\User;
use App\Models\Wrestler;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * @group wrestlers
 * @group admins
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
    public function an_administrator_can_view_the_form_for_creating_a_wrestler()
    {
        $user = factory(User::class)->states('administrator')->create();

        $response = $this->actingAs($user)
                        ->get(route('wrestlers.create'));

        $response->assertViewIs('wrestlers.create');
        $response->assertViewHas('wrestler', new Wrestler);
    }

    /** @test */
    public function an_administrator_can_create_a_wrestler()
    {
        $user = factory(User::class)->states('administrator')->create();

        $response = $this->actingAs($user)
                        ->from(route('wrestlers.create'))
                        ->post(route('wrestlers.store'), $this->validParams());

        $response->assertRedirect(route('wrestlers.index'));
        tap(Wrestler::first(), function ($wrestler) {
            $this->assertEquals('Example Wrestler Name', $wrestler->name);
            $this->assertEquals(76, $wrestler->height);
            $this->assertEquals(240, $wrestler->weight);
            $this->assertEquals('Laraville, FL', $wrestler->hometown);
            $this->assertEquals('The Finisher', $wrestler->signature_move);
            $this->assertEquals(today()->toDateString(), $wrestler->hired_at->toDateString());
        });
    }

    /** @test */
    public function a_wrestler_hired_today_or_before_is_bookable()
    {
        $user = factory(User::class)->states('administrator')->create();

        $this->actingAs($user)
            ->from(route('wrestlers.create'))
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
        $user = factory(User::class)->states('administrator')->create();

        $this->actingAs($user)
            ->from(route('wrestlers.create'))
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
        $user = factory(User::class)->states('administrator')->create();

        $response = $this->actingAs($user)
                        ->from(route('wrestlers.create'))
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
