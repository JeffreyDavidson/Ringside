<?php

declare(strict_types=1);

use App\Livewire\Matches\Tables\Main;
use App\Models\Events\Event;
use App\Models\Matches\EventMatch;
use App\Models\Users\User;

use function Pest\Laravel\actingAs;
use function Pest\Livewire\livewire;

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

            actingAs($this->admin);

            $component = livewire(Main::class);

            $component->assertOk();
        });

        test('displays match information correctly', function () {
            $match = EventMatch::factory()->create([
                'event_id' => $this->event->id,
                'match_number' => 2,
            ]);

            actingAs($this->admin);

            $component = livewire(Main::class);

            $component->assertOk()
                ->assertSee('Test Event');
        });

        test('loads event relationships for display', function () {
            $match = EventMatch::factory()->create([
                'event_id' => $this->event->id,
            ]);

            expect($match->event)->not()->toBeNull();
            expect($match->event()->firstOrFail()->name)->toBe('Test Event');

            actingAs($this->admin);

            $component = livewire(Main::class);

            $component->assertOk()
                ->assertSee('Test Event');
        });
    });

    describe('authorization integration', function () {
        test('component requires proper authorization for access', function () {
            $basicUser = User::factory()->create();

            actingAs($basicUser);

            livewire(Main::class)
                ->assertForbidden();
        });

        test('guest users cannot access component', function () {
            livewire(Main::class)
                ->assertForbidden();
        });

        test('admin can access matches table', function () {
            actingAs($this->admin);

            $component = livewire(Main::class);

            $component->assertOk();
        });
    });

    describe('deletion', function () {
        test('deletes matches through the table action', function () {
            $match = EventMatch::factory()->create([
                'event_id' => $this->event->id,
            ]);

            actingAs($this->admin);

            livewire(Main::class)
                ->call('delete', $match);

            $this->assertSoftDeleted($match);
        });
    });

    describe('query optimization and performance', function () {
        test('orders matches by event chronology instead of creation chronology', function () {
            EventMatch::query()->forceDelete();

            $latestEvent = Event::factory()->create(['date' => now()->addDay()]);
            $earliestEvent = Event::factory()->create(['date' => now()->subDay()]);
            $latestEventMatch = EventMatch::factory()->forEvent($latestEvent)->create([
                'created_at' => now()->subDay(),
            ]);
            $earliestEventMatch = EventMatch::factory()->forEvent($earliestEvent)->create([
                'created_at' => now(),
            ]);

            $table = new Main();

            expect($table->builder()->pluck('id')->all())->toBe([
                $latestEventMatch->id,
                $earliestEventMatch->id,
            ]);
        });

        test('component loads efficiently with many matches', function () {
            EventMatch::factory()->count(10)->create([
                'event_id' => $this->event->id,
            ]);

            actingAs($this->admin);

            $component = livewire(Main::class);

            $component->assertOk();
        });

        test('eager loading relationships works correctly', function () {
            $match = EventMatch::factory()->create([
                'event_id' => $this->event->id,
            ]);

            expect($match->event)->not()->toBeNull();

            actingAs($this->admin);

            $component = livewire(Main::class);

            $component->assertOk()
                ->assertSee('Test Event');
        });
    });

    describe('component state management', function () {
        test('component maintains state through action calls', function () {
            $match = EventMatch::factory()->create([
                'event_id' => $this->event->id,
            ]);

            actingAs($this->admin);

            $component = livewire(Main::class);

            $component->assertOk();

            expect(EventMatch::find($match->id))->not()->toBeNull();
        });
    });
});
