<?php

declare(strict_types=1);

namespace Tests\Integration\Models;

use App\Enums\TitleStatus;
use App\Models\Title;
use Tests\Integration\Models\Concerns\RetirableContractTests;
use Tests\TestCase;

/**
 * @group titles
 */
class TitleTest extends TestCase
{
    use RetirableContractTests;

    private $activeTitle;

    private $futureActivatedTitle;

    private $inactiveTitle;

    private $retiredTitle;

    protected function setUp(): void
    {
        parent::setUp();

        $this->activeTitle = Title::factory()->active()->create();
        $this->futureActivatedTitle = Title::factory()->withFutureActivation()->create();
        $this->inactiveTitle = Title::factory()->inactive()->create();
        $this->retiredTitle = Title::factory()->retired()->create();
    }

    protected function getActivatable()
    {
        return Title::factory()->active()->create();
    }

    protected function getRetirable()
    {
        return Title::factory()->retired()->create();
    }

    /**
     * @test
     */
    public function a_title_has_a_name()
    {
        $wrestler = Title::factory()->create(['name' => 'Example Title Name']);

        $this->assertEquals('Example Title Name', $wrestler->name);
    }

    /**
     * @test
     */
    public function a_title_has_a_status()
    {
        $title = Title::factory()->create();

        $this->assertInstanceOf(TitleStatus::class, $title->status);
    }

    /**
     * @test
     */
    public function it_can_get_active_titles()
    {
        $activeTitles = Title::active()->get();

        $this->assertCount(1, $activeTitles);
        $this->assertCollectionHas($activeTitles, $this->activeTitle);
        $this->assertCollectionDoesntHave($activeTitles, $this->futureActivatedTitle);
        $this->assertCollectionDoesntHave($activeTitles, $this->inactiveTitle);
        $this->assertCollectionDoesntHave($activeTitles, $this->retiredTitle);
    }

    /**
     * @test
     */
    public function it_can_get_future_activated_titles()
    {
        $futureActivatedTitles = Title::withFutureActivation()->get();

        $this->assertCount(1, $futureActivatedTitles);
        $this->assertCollectionHas($futureActivatedTitles, $this->futureActivatedTitle);
        $this->assertCollectionDoesntHave($futureActivatedTitles, $this->activeTitle);
        $this->assertCollectionDoesntHave($futureActivatedTitles, $this->inactiveTitle);
        $this->assertCollectionDoesntHave($futureActivatedTitles, $this->retiredTitle);
    }

    /**
     * @test
     */
    public function it_can_get_inactive_titles()
    {
        $inactiveTitles = Title::inactive()->get();

        $this->assertCount(1, $inactiveTitles);
        $this->assertCollectionHas($inactiveTitles, $this->inactiveTitle);
        $this->assertCollectionDoesntHave($inactiveTitles, $this->futureActivatedTitle);
        $this->assertCollectionDoesntHave($inactiveTitles, $this->activeTitle);
        $this->assertCollectionDoesntHave($inactiveTitles, $this->retiredTitle);
    }

    /**
     * @test
     */
    public function it_can_get_retired_titles()
    {
        $retiredTitles = Title::retired()->get();

        $this->assertCount(1, $retiredTitles);
        $this->assertCollectionHas($retiredTitles, $this->retiredTitle);
        $this->assertCollectionDoesntHave($retiredTitles, $this->futureActivatedTitle);
        $this->assertCollectionDoesntHave($retiredTitles, $this->activeTitle);
        $this->assertCollectionDoesntHave($retiredTitles, $this->inactiveTitle);
    }
}
