<?php

namespace Tests\Feature\Referees;

use App\Enums\Role;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Factories\RefereeFactory;
use Tests\TestCase;
use TRegx\DataProvider\DataProviders;

/**
 * @group referees
 * @group roster
 */
class DeleteRefereeSuccessConditionsTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @test
     * @dataProvider providers
     */
    public function administrators_can_delete_referees($adminRoles, $refereeStatuses)
    {
        $this->actAs($adminRoles);
        $referee = RefereeFactory::new()->$refereeStatuses()->create();

        $this->deleteRequest($referee);

        $this->assertSoftDeleted($referee);
    }

    public function providers()
    {
        return DataProviders::cross(
            $this->adminRoles(),
            $this->refereeStatuses()
        );
    }

    public function adminRoles()
    {
        return [
            [Role::ADMINISTRATOR],
            [Role::SUPER_ADMINISTRATOR],
        ];
    }

    public function refereeStatuses()
    {
        return [
            ['bookable'],
            ['pendingEmployment'],
            ['retired'],
            ['suspended'],
            ['injured'],
        ];
    }
}
