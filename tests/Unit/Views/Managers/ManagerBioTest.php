<?php

namespace Tests\Unit\Views\Managers;

use Illuminate\Foundation\Testing\RefreshDatabase;
use NunoMaduro\LaravelMojito\InteractsWithViews;
use Tests\Factories\ManagerFactory;
use Tests\TestCase;

class ManagerBioTest extends TestCase
{
    use RefreshDatabase, InteractsWithViews;

    /** @test */
    public function a_manager_name_can_be_seen_on_their_biography_page()
    {
        $manager = ManagerFactory::new()->create(['first_name' => 'John', 'last_name' => 'Smith']);

        $this->assertView('managers.show', compact('manager'))->contains('John Smith');
    }
}
