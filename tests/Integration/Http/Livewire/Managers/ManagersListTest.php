<?php

declare(strict_types=1);

namespace Tests\Integration\Http\Livewire\Managers;

use App\Http\Livewire\Managers\ManagersList;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * @group managers
 * @group integration-managers
 */
class ManagersListTest extends TestCase
{
    /**
     * @test
     */
    public function it_should_return_correct_view()
    {
        Livewire::test(ManagersList::class)
            ->assertViewIs('livewire.managers.managers-list');
    }

    /**
     * @test
     */
    public function it_should_pass_correct_data()
    {
        Livewire::test(ManagersList::class)
            ->assertViewHas('managers');
    }
}
