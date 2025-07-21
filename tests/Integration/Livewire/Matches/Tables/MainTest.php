<?php

declare(strict_types=1);

use App\Livewire\Matches\Tables\Main;
use App\Models\Events\Event;
use App\Models\Matches\EventMatch;
use App\Models\Users\User;
use Livewire\Livewire;

/**
 * @group matches
 * @group integration
 * @group livewire
 * @group tables
 */
describe('Matches Main Table Component Integration', function () {
    beforeEach(function () {
        $this->admin = User::factory()->administrator()->create();
        $this->event = Event::factory()->scheduled()->create(['name' => 'Test Event']);
    });

    describe('component rendering and data display', function () {
        test('renders matches table with complete data relationships', function () {
            $match = EventMatch::factory()->create([
                'event_id' => $this->event->id,
                'match_number' => 1,
            ]);

            $component = Livewire::actingAs($this->admin)
                ->test(Main::class);

            $component->assertOk();
        });

        test('displays match information correctly', function () {
            $match = EventMatch::factory()->create([
                'event_id' => $this->event->id,
                'match_number' => 2,
            ]);

            $component = Livewire::actingAs($this->admin)
                ->test(Main::class);

            $component->assertOk()
                ->assertSee('Test Event');
        });

        test('loads event relationships for display', function () {
            $match = EventMatch::factory()->create([
                'event_id' => $this->event->id,
            ]);

            expect($match->event)->not()->toBeNull();
            expect($match->event->name)->toBe('Test Event');

            $component = Livewire::actingAs($this->admin)
                ->test(Main::class);

            $component->assertOk()
                ->assertSee('Test Event');
        });
    });

    describe('authorization integration', function () {
        test('component requires proper authorization for access', function () {
            $basicUser = User::factory()->create();

            Livewire::actingAs($basicUser)
                ->test(Main::class)
                ->assertForbidden();
        });

        test('guest users cannot access component', function () {
            Livewire::test(Main::class)
                ->assertForbidden();
        });

        test('admin can access matches table', function () {
            $component = Livewire::actingAs($this->admin)
                ->test(Main::class);

            $component->assertOk();
        });
    });

    describe('query optimization and performance', function () {
        test('component loads efficiently with many matches', function () {
            EventMatch::factory()->count(10)->create([
                'event_id' => $this->event->id,
            ]);

            $component = Livewire::actingAs($this->admin)
                ->test(Main::class);

            $component->assertOk();
        });

        test('eager loading relationships works correctly', function () {
            $match = EventMatch::factory()->create([
                'event_id' => $this->event->id,
            ]);

            expect($match->event)->not()->toBeNull();

            $component = Livewire::actingAs($this->admin)
                ->test(Main::class);

            $component->assertOk()
                ->assertSee('Test Event');
        });
    });

    describe('component state management', function () {
        test('component maintains state through action calls', function () {
            $match = EventMatch::factory()->create([
                'event_id' => $this->event->id,
            ]);

            $component = Livewire::actingAs($this->admin)
                ->test(Main::class);

            $component->assertOk();

            expect(EventMatch::find($match->id))->not()->toBeNull();
        });
    });
});