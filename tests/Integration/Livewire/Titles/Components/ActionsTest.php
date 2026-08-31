<?php

declare(strict_types=1);

use App\Livewire\Titles\Components\Actions;
use App\Models\Titles\Title;
use App\Models\Users\User;
use Livewire\Livewire;

use function Pest\Laravel\actingAs;
use function Pest\Livewire\livewire;

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
        $component = livewire(Actions::class, ['title' => $this->title]);

        $component->assertOk();
        $component->assertSet('title.id', $this->title->id);
        $component->assertSet('title.name', 'Test Championship Title');
    });

    it('renders actions component view', function () {
        $component = livewire(Actions::class, ['title' => $this->title]);

        $component->assertViewIs('livewire.titles.components.actions');
    });
});

describe('Title Debut Actions', function () {
    it('can debut an undebuted title successfully', function () {
        $title = Title::factory()->undebuted()->create();

        actingAs($this->admin);

        $component = livewire(Actions::class, ['title' => $title])
            ->call('debut');

        $component->assertHasNoErrors();
        $component->assertDispatched('title-updated');
        $component->assertDispatched(
            'flash-message',
            type: 'status',
            message: 'Title successfully debuted.',
        );

        // Verify the title status changed through the action
        expect(freshModel($title)->currentActivityPeriod()->exists())->toBeTrue();
    });

    it('handles debut for already active title', function () {
        $title = Title::factory()->active()->create();

        actingAs($this->admin);

        $component = livewire(Actions::class, ['title' => $title])
            ->call('debut');

        $component->assertHasNoErrors();
    });
});

describe('Title Retirement Actions', function () {
    it('can retire an active title successfully', function () {
        $title = Title::factory()->active()->create();

        actingAs($this->admin);

        $component = livewire(Actions::class, ['title' => $title])
            ->call('retire');

        $component->assertHasNoErrors();
        $component->assertDispatched('title-updated');

        // Verify the title status changed
        expect(freshModel($title)->currentRetirement()->exists())->toBeTrue();
    });

    it('can unretire a retired title successfully', function () {
        $title = Title::factory()->retired()->create();

        actingAs($this->admin);

        $component = livewire(Actions::class, ['title' => $title])
            ->call('unretire');

        $component->assertHasNoErrors();
        $component->assertDispatched('title-updated');

        // Verify the title is no longer retired
        expect(freshModel($title)->currentRetirement()->exists())->toBeFalse();
    });
});

describe('Title Activation Actions', function () {
    it('can deactivate (pull) an active title successfully', function () {
        $title = Title::factory()->active()->create();

        actingAs($this->admin);

        $component = livewire(Actions::class, ['title' => $title])
            ->call('deactivate');

        $component->assertHasNoErrors();
        $component->assertDispatched('title-updated');
    });

    it('can reinstate an inactive title successfully', function () {
        $title = Title::factory()->inactive()->create();

        actingAs($this->admin);

        $component = livewire(Actions::class, ['title' => $title])
            ->call('reinstate');

        $component->assertHasNoErrors();
        $component->assertDispatched('title-updated');
    });
});

describe('Title Restoration Actions', function () {
    it('can restore a deleted title successfully', function () {
        $this->title->delete();
        expect($this->title->trashed())->toBeTrue();

        $trashedTitle = Title::onlyTrashed()->findOrFail($this->title->id);

        actingAs($this->admin);

        $component = livewire(Actions::class, ['title' => $trashedTitle])
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
            actingAs($user);

            $component = livewire(Actions::class, ['title' => $this->title]);

            $component->call($method)
                ->assertForbidden();
        }
    });
});

describe('Title Actions Event Dispatching', function () {
    it('dispatches title-updated event on successful actions', function () {
        $title = Title::factory()->undebuted()->create();

        actingAs($this->admin);

        $component = livewire(Actions::class, ['title' => $title]);

        $component->call('debut')
            ->assertDispatched('title-updated');
    });

    it('does not dispatch events on failed actions', function () {
        $user = User::factory()->create(); // Non-admin user

        Livewire::actingAs($user);

        $component = livewire(Actions::class, ['title' => $this->title]);

        $component->call('debut')->assertForbidden();
        $component->assertNotDispatched('title-updated');
    });
});

describe('Title Business Logic Integration', function () {
    it('handles complete title lifecycle', function () {
        // Start with undebuted title
        $title = Title::factory()->undebuted()->create();
        actingAs($this->admin);

        $component = livewire(Actions::class, ['title' => $title]);

        // Debut the title
        $component->call('debut');
        expect(freshModel($title)->currentActivityPeriod()->exists())->toBeTrue();

        // Retire the title
        $component->call('retire');
        expect(freshModel($title)->currentRetirement()->exists())->toBeTrue();

        // Unretire the title
        $component->call('unretire');
        expect(freshModel($title)->currentRetirement()->exists())->toBeFalse();
    });

    it('maintains title state consistency', function () {
        $originalName = $this->title->name;
        $originalId = $this->title->id;

        actingAs($this->admin);

        $component = livewire(Actions::class, ['title' => $this->title]);

        $component->call('debut');

        // Title identity should remain consistent
        expect($component->get('title')->name)->toBe($originalName);
        expect($component->get('title')->id)->toBe($originalId);
    });
});

it('provides translated title action messages', function (string $action, string $message) {
    expect(__("titles.actions.{$action}"))->toBe($message);
})->with([
    'debuted' => ['debuted', 'Title successfully debuted.'],
    'retired' => ['retired', 'Title successfully retired.'],
    'unretired' => ['unretired', 'Title successfully unretired.'],
    'pulled' => ['pulled', 'Title successfully pulled.'],
    'reinstated' => ['reinstated', 'Title successfully reinstated.'],
    'restored' => ['restored', 'Title successfully restored.'],
]);
