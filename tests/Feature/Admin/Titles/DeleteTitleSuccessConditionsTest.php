<?php

namespace Tests\Feature\Admin\Titles;

use App\Enums\Role;
use Tests\TestCase;
use App\Models\Title;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * @group titles
 * @group admins
 */
class DeleteTitleSuccessConditionsTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function an_administrator_can_delete_a_competable_title()
    {
        $this->actAs(Role::ADMINISTRATOR);
        $title = TitleFactory::new()->competable()->create();

        $response = $this->deleteRequest($title);

        $response->assertRedirect(route('titles.index'));
        $this->assertSoftDeleted('titles', ['name' => $title->name]);
    }

    /** @test */
    public function an_administrator_can_delete_a_pending_introduction_title()
    {
        $this->actAs(Role::ADMINISTRATOR);
        $title = TitleFactory::new()->pendingIntroduction()->create();

        $response = $this->delete(route('titles.destroy', $title));

        $response->assertRedirect(route('titles.index'));
        $this->assertSoftDeleted('titles', ['name' => $title->name]);
    }

    /** @test */
    public function an_administrator_can_delete_a_retired_title()
    {
        $this->actAs(Role::ADMINISTRATOR);
        $title = factory(Title::class)->states('retired')->create();

        $response = $this->delete(route('titles.destroy', $title));

        $response->assertRedirect(route('titles.index'));
        $this->assertSoftDeleted('titles', ['name' => $title->name]);
    }
}
