<?php

namespace Tests\Feature\User\Wrestlers;

use Tests\TestCase;
use App\Models\Wrestler;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * @group wrestlers
 * @group users
 */
class ViewWrestlersListSuccessConditionsTest extends TestCase
{
    use RefreshDatabase;

    /** @var \Illuminate\Support\Collection */
    protected $wrestlers;

    /**
     * Setup the test environment.
     *
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();

        $mapToIdAndName = function (Wrestler $wrestler) {
            return ['id' => $wrestler->id, 'name' => e($wrestler->name)];
        };

        $bookable  = factory(Wrestler::class, 3)->states('bookable')->create()->map($mapToIdAndName);
        $inactive  = factory(Wrestler::class, 3)->states('inactive')->create()->map($mapToIdAndName);
        $retired   = factory(Wrestler::class, 3)->states('retired')->create()->map($mapToIdAndName);
        $suspended = factory(Wrestler::class, 3)->states('suspended')->create()->map($mapToIdAndName);
        $injured   = factory(Wrestler::class, 3)->states('injured')->create()->map($mapToIdAndName);

        $this->wrestlers = collect([
            'bookable'  => $bookable,
            'inactive'  => $inactive,
            'retired'   => $retired,
            'suspended' => $suspended,
            'injured'   => $injured,
            'all'       => collect()
                ->concat($bookable)
                ->concat($inactive)
                ->concat($retired)
                ->concat($suspended)
                ->concat($injured)
        ]);
    }

    /** @test */
    public function a_basic_user_cannot_view_wrestlers_page()
    {
        $this->actAs('basic-user');

        $response = $this->get(route('wrestlers.index'));

        $response->assertForbidden();
    }
}
