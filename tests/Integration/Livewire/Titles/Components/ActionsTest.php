<?php

declare(strict_types=1);

use App\Livewire\Titles\Components\Actions;
use App\Models\Titles\Title;
use App\Models\Users\User;
use Livewire\Livewire;

/**
 * Title Actions Component Integration Tests
 *
 * @group titles
 * @group integration
 * @group livewire
 * @group actions
 *
 * Tests the complete business action workflow for titles including:
 * - Title debut lifecycle (debut, retirement, unretirement)
 * - Title activation management (deactivate/pull, reinstate)
 * - Title restoration for deleted titles
 * - Status transitions and validation
 * - Authorization integration
 * - Event dispatching and state management
 */
beforeEach(function () {
    $this->admin = User::factory()->administrator()->create();
    $this->actingAs($this->admin);
    
    $this->title = Title::factory()->create([
        'name' => 'Test Championship Title',
    ]);
});

describe('Actions Component Initialization', function () {
    it('can mount with title', function () {
        $component = Livewire::test(Actions::class, ['title' => $this->title]);
        
        $component->assertOk();
        $component->assertSet('title.id', $this->title->id);
        $component->assertSet('title.name', 'Test Championship Title');
    });

    it('renders actions component view', function () {
        $component = Livewire::test(Actions::class, ['title' => $this->title]);
        
        $component->assertViewIs('livewire.titles.components.actions');
    });
});

describe('Title Debut Actions', function () {
    it('can debut an undebuted title successfully', function () {
        $title = Title::factory()->undebuted()->create();

        $component = Livewire::actingAs($this->admin)
            ->test(Actions::class, ['title' => $title])
            ->call('debut');

        $component->assertHasNoErrors();
        $component->assertDispatched('title-updated');
        
        // Verify the title status changed through the action
        expect($title->fresh()->isCurrentlyActive())->toBeTrue();
    });

    it('handles debut for already active title', function () {
        $title = Title::factory()->active()->create();

        $component = Livewire::actingAs($this->admin)
            ->test(Actions::class, ['title' => $title])
            ->call('debut');

        // Should handle gracefully without errors
        $component->assertHasNoErrors();
    });
});

describe('Title Retirement Actions', function () {
    it('can retire an active title successfully', function () {
        $title = Title::factory()->active()->create();

        $component = Livewire::actingAs($this->admin)
            ->test(Actions::class, ['title' => $title])
            ->call('retire');

        $component->assertHasNoErrors();
        $component->assertDispatched('title-updated');
        
        // Verify the title status changed
        expect($title->fresh()->isRetired())->toBeTrue();
    });

    it('can unretire a retired title successfully', function () {
        $title = Title::factory()->retired()->create();

        $component = Livewire::actingAs($this->admin)
            ->test(Actions::class, ['title' => $title])
            ->call('unretire');

        $component->assertHasNoErrors();
        $component->assertDispatched('title-updated');
        
        // Verify the title is no longer retired
        expect($title->fresh()->isRetired())->toBeFalse();
    });
});

describe('Title Activation Actions', function () {
    it('can deactivate (pull) an active title successfully', function () {
        $title = Title::factory()->active()->create();

        $component = Livewire::actingAs($this->admin)
            ->test(Actions::class, ['title' => $title])
            ->call('deactivate');

        $component->assertHasNoErrors();
        $component->assertDispatched('title-updated');
    });

    it('can reinstate an inactive title successfully', function () {
        $title = Title::factory()->inactive()->create();

        $component = Livewire::actingAs($this->admin)
            ->test(Actions::class, ['title' => $title])
            ->call('reinstate');

        $component->assertHasNoErrors();
        $component->assertDispatched('title-updated');
    });
});

describe('Title Restoration Actions', function () {
    it('can restore a deleted title successfully', function () {
        $this->title->delete();
        expect($this->title->trashed())->toBeTrue();

        $trashedTitle = Title::onlyTrashed()->find($this->title->id);

        $component = Livewire::actingAs($this->admin)
            ->test(Actions::class, ['title' => $trashedTitle])
            ->call('restore');

        $component->assertHasNoErrors();
        $component->assertDispatched('title-updated');

        expect(Title::find($this->title->id))->not()->toBeNull();
    })->group('titles', 'integration', 'livewire', 'actions', 'restore');
});

describe('Title Actions Authorization', function () {
    it('enforces authorization for all actions', function () {
        $user = User::factory()->create(); // Non-admin user

        $actions = ['debut', 'retire', 'unretire', 'deactivate', 'reinstate', 'restore'];
        
        foreach ($actions as $method) {
            $component = Livewire::actingAs($user)
                ->test(Actions::class, ['title' => $this->title]);
            
            $component->call($method)
                ->assertForbidden();
        }
    });
});

describe('Title Actions Event Dispatching', function () {
    it('dispatches title-updated event on successful actions', function () {
        $title = Title::factory()->undebuted()->create();
        
        $component = Livewire::actingAs($this->admin)
            ->test(Actions::class, ['title' => $title]);

        $component->call('debut')
            ->assertDispatched('title-updated');
    });

    it('does not dispatch events on failed actions', function () {
        $user = User::factory()->create(); // Non-admin user
        
        $component = Livewire::actingAs($user)
            ->test(Actions::class, ['title' => $this->title]);

        $component->call('debut')
            ->assertForbidden()
            ->assertNotDispatched('title-updated');
    });
});

describe('Title Business Logic Integration', function () {
    it('handles complete title lifecycle', function () {
        // Start with undebuted title
        $title = Title::factory()->undebuted()->create();
        $component = Livewire::actingAs($this->admin)
            ->test(Actions::class, ['title' => $title]);

        // Debut the title
        $component->call('debut');
        expect($title->fresh()->isCurrentlyActive())->toBeTrue();

        // Retire the title
        $component->call('retire');
        expect($title->fresh()->isRetired())->toBeTrue();

        // Unretire the title
        $component->call('unretire');
        expect($title->fresh()->isRetired())->toBeFalse();
    });

    it('maintains title state consistency', function () {
        $originalName = $this->title->name;
        $originalId = $this->title->id;

        $component = Livewire::actingAs($this->admin)
            ->test(Actions::class, ['title' => $this->title]);

        $component->call('debut');

        // Title identity should remain consistent
        expect($component->get('title')->name)->toBe($originalName);
        expect($component->get('title')->id)->toBe($originalId);
    });
});