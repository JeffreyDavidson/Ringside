<?php

namespace Tests\Feature\Generic\Wrestlers;

use App\Models\Wrestler;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * @group wrestlers
 * @group generics
 */
class ViewWrestlerBioPageSuccessConditionsTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function a_wrestlers_data_can_be_seen_on_their_profile()
    {
        $this->actAs('administrator');

        $wrestler = factory(Wrestler::class)->create([
            'name' => 'Wrestler 1',
            'height' => 78,
            'weight' => 220,
            'hometown' => 'Laraville, FL',
            'signature_move' => 'The Finisher',
        ]);

        $response = $this->get(route('wrestlers.show', ['wrestler' => $wrestler]));

        $response->assertSee('Wrestler 1');
        $response->assertSee(e('6\'6"'));
        $response->assertSee('220 lbs');
        $response->assertSee('Laraville, FL');
        $response->assertSee('The Finisher');
    }
}
