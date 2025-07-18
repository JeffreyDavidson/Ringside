<?php

declare(strict_types=1);

use App\Actions\Titles\DebutAction;
use App\Actions\Titles\PullAction;
use App\Actions\Titles\ReinstateAction;
use App\Actions\Titles\RestoreAction;
use App\Actions\Titles\RetireAction;
use App\Actions\Titles\UnretireAction;
use App\Exceptions\Status\CannotBeDebutedException;
use App\Exceptions\Status\CannotBePulledException;
use App\Exceptions\Status\CannotBeReinstatedException;
use App\Exceptions\Status\CannotBeRetiredException;
use App\Exceptions\Status\CannotBeUnretiredException;
use App\Livewire\Titles\Components\Actions;
use App\Models\Titles\Title;
use App\Models\Users\User;
use Illuminate\Support\Facades\Gate;
use Livewire\Livewire;
use Mockery\MockInterface;

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
        $component->assertSet('title', $this->title);
    });

    it('renders actions component view', function () {
        $component = Livewire::test(Actions::class, ['title' => $this->title]);
        
        $component->assertViewIs('livewire.titles.components.actions');
    });
});

describe('Title Debut Actions', function () {
    it('can debut a title successfully', function () {
        $mockAction = $this->mock(DebutAction::class, function (MockInterface $mock) {
            $mock->shouldReceive('handle')
                ->once()
                ->with($this->title);
        });

        Gate::shouldReceive('authorize')
            ->once()
            ->with('debut', $this->title);

        $component = Livewire::test(Actions::class, ['title' => $this->title])
            ->call('debut');

        $component->assertDispatched('title-updated');
        $component->assertSessionHas('status', 'Title successfully debuted.');
    });

    it('handles debut authorization failure', function () {
        Gate::shouldReceive('authorize')
            ->once()
            ->with('debut', $this->title)
            ->andThrow(new \Illuminate\Auth\Access\AuthorizationException());

        $component = Livewire::test(Actions::class, ['title' => $this->title]);
        
        expect(fn() => $component->call('debut'))
            ->toThrow(\Illuminate\Auth\Access\AuthorizationException::class);
    });

    it('handles debut business logic failure', function () {
        Gate::shouldReceive('authorize')
            ->once()
            ->with('debut', $this->title);

        $mockAction = $this->mock(DebutAction::class, function (MockInterface $mock) {
            $mock->shouldReceive('handle')
                ->once()
                ->with($this->title)
                ->andThrow(new CannotBeDebutedException('Title cannot be debuted'));
        });

        $component = Livewire::test(Actions::class, ['title' => $this->title])
            ->call('debut');

        $component->assertSessionHas('error', 'Title cannot be debuted');
        $component->assertNotDispatched('title-updated');
    });
});

describe('Title Retirement Actions', function () {
    it('can retire a title successfully', function () {
        $mockAction = $this->mock(RetireAction::class, function (MockInterface $mock) {
            $mock->shouldReceive('handle')
                ->once()
                ->with($this->title);
        });

        Gate::shouldReceive('authorize')
            ->once()
            ->with('retire', $this->title);

        $component = Livewire::test(Actions::class, ['title' => $this->title])
            ->call('retire');

        $component->assertDispatched('title-updated');
        $component->assertSessionHas('status', 'Title successfully retired.');
    });

    it('handles retirement business logic failure', function () {
        Gate::shouldReceive('authorize')
            ->once()
            ->with('retire', $this->title);

        $mockAction = $this->mock(RetireAction::class, function (MockInterface $mock) {
            $mock->shouldReceive('handle')
                ->once()
                ->with($this->title)
                ->andThrow(new CannotBeRetiredException('Title cannot be retired'));
        });

        $component = Livewire::test(Actions::class, ['title' => $this->title])
            ->call('retire');

        $component->assertSessionHas('error', 'Title cannot be retired');
        $component->assertNotDispatched('title-updated');
    });

    it('can unretire a title successfully', function () {
        $mockAction = $this->mock(UnretireAction::class, function (MockInterface $mock) {
            $mock->shouldReceive('handle')
                ->once()
                ->with($this->title);
        });

        Gate::shouldReceive('authorize')
            ->once()
            ->with('unretire', $this->title);

        $component = Livewire::test(Actions::class, ['title' => $this->title])
            ->call('unretire');

        $component->assertDispatched('title-updated');
        $component->assertSessionHas('status', 'Title successfully unretired.');
    });

    it('handles unretire business logic failure', function () {
        Gate::shouldReceive('authorize')
            ->once()
            ->with('unretire', $this->title);

        $mockAction = $this->mock(UnretireAction::class, function (MockInterface $mock) {
            $mock->shouldReceive('handle')
                ->once()
                ->with($this->title)
                ->andThrow(new CannotBeUnretiredException('Title cannot be unretired'));
        });

        $component = Livewire::test(Actions::class, ['title' => $this->title])
            ->call('unretire');

        $component->assertSessionHas('error', 'Title cannot be unretired');
        $component->assertNotDispatched('title-updated');
    });
});

describe('Title Activation Actions', function () {
    it('can deactivate (pull) a title successfully', function () {
        $mockAction = $this->mock(PullAction::class, function (MockInterface $mock) {
            $mock->shouldReceive('handle')
                ->once()
                ->with($this->title);
        });

        Gate::shouldReceive('authorize')
            ->once()
            ->with('pull', $this->title);

        $component = Livewire::test(Actions::class, ['title' => $this->title])
            ->call('deactivate');

        $component->assertDispatched('title-updated');
        $component->assertSessionHas('status', 'Title successfully pulled.');
    });

    it('handles deactivate business logic failure', function () {
        Gate::shouldReceive('authorize')
            ->once()
            ->with('pull', $this->title);

        $mockAction = $this->mock(PullAction::class, function (MockInterface $mock) {
            $mock->shouldReceive('handle')
                ->once()
                ->with($this->title)
                ->andThrow(new CannotBePulledException('Title cannot be pulled'));
        });

        $component = Livewire::test(Actions::class, ['title' => $this->title])
            ->call('deactivate');

        $component->assertSessionHas('error', 'Title cannot be pulled');
        $component->assertNotDispatched('title-updated');
    });

    it('can reinstate a title successfully', function () {
        $mockAction = $this->mock(ReinstateAction::class, function (MockInterface $mock) {
            $mock->shouldReceive('handle')
                ->once()
                ->with($this->title);
        });

        Gate::shouldReceive('authorize')
            ->once()
            ->with('reinstate', $this->title);

        $component = Livewire::test(Actions::class, ['title' => $this->title])
            ->call('reinstate');

        $component->assertDispatched('title-updated');
        $component->assertSessionHas('status', 'Title successfully reinstated.');
    });

    it('handles reinstate business logic failure', function () {
        Gate::shouldReceive('authorize')
            ->once()
            ->with('reinstate', $this->title);

        $mockAction = $this->mock(ReinstateAction::class, function (MockInterface $mock) {
            $mock->shouldReceive('handle')
                ->once()
                ->with($this->title)
                ->andThrow(new CannotBeReinstatedException('Title cannot be reinstated'));
        });

        $component = Livewire::test(Actions::class, ['title' => $this->title])
            ->call('reinstate');

        $component->assertSessionHas('error', 'Title cannot be reinstated');
        $component->assertNotDispatched('title-updated');
    });
});

describe('Title Restoration Actions', function () {
    it('can restore a deleted title successfully', function () {
        $mockAction = $this->mock(RestoreAction::class, function (MockInterface $mock) {
            $mock->shouldReceive('handle')
                ->once()
                ->with($this->title);
        });

        Gate::shouldReceive('authorize')
            ->once()
            ->with('restore', $this->title);

        $component = Livewire::test(Actions::class, ['title' => $this->title])
            ->call('restore');

        $component->assertDispatched('title-updated');
        $component->assertSessionHas('status', 'Title successfully restored.');
    });

    it('handles restore authorization failure', function () {
        Gate::shouldReceive('authorize')
            ->once()
            ->with('restore', $this->title)
            ->andThrow(new \Illuminate\Auth\Access\AuthorizationException());

        $component = Livewire::test(Actions::class, ['title' => $this->title]);
        
        expect(fn() => $component->call('restore'))
            ->toThrow(\Illuminate\Auth\Access\AuthorizationException::class);
    });
});

describe('Title Actions Authorization', function () {
    it('enforces authorization for all actions', function () {
        $actions = [
            'debut' => 'debut',
            'retire' => 'retire',
            'unretire' => 'unretire',
            'deactivate' => 'pull',
            'reinstate' => 'reinstate',
            'restore' => 'restore',
        ];

        foreach ($actions as $method => $permission) {
            Gate::shouldReceive('authorize')
                ->once()
                ->with($permission, $this->title)
                ->andThrow(new \Illuminate\Auth\Access\AuthorizationException());

            $component = Livewire::test(Actions::class, ['title' => $this->title]);
            
            expect(fn() => $component->call($method))
                ->toThrow(\Illuminate\Auth\Access\AuthorizationException::class);
        }
    });
});

describe('Title Actions Event Dispatching', function () {
    it('dispatches title-updated event on successful actions', function () {
        $actions = ['debut', 'retire', 'unretire', 'deactivate', 'reinstate', 'restore'];

        foreach ($actions as $action) {
            // Mock the appropriate action class
            $actionClass = match ($action) {
                'debut' => DebutAction::class,
                'retire' => RetireAction::class,
                'unretire' => UnretireAction::class,
                'deactivate' => PullAction::class,
                'reinstate' => ReinstateAction::class,
                'restore' => RestoreAction::class,
            };

            $mockAction = $this->mock($actionClass, function (MockInterface $mock) {
                $mock->shouldReceive('handle');
            });

            Gate::shouldReceive('authorize')->andReturn(true);

            $component = Livewire::test(Actions::class, ['title' => $this->title])
                ->call($action);

            $component->assertDispatched('title-updated');
        }
    });

    it('does not dispatch events on failed actions', function () {
        $actions = [
            'debut' => CannotBeDebutedException::class,
            'retire' => CannotBeRetiredException::class,
            'unretire' => CannotBeUnretiredException::class,
            'deactivate' => CannotBePulledException::class,
            'reinstate' => CannotBeReinstatedException::class,
        ];

        foreach ($actions as $action => $exceptionClass) {
            $actionClass = match ($action) {
                'debut' => DebutAction::class,
                'retire' => RetireAction::class,
                'unretire' => UnretireAction::class,
                'deactivate' => PullAction::class,
                'reinstate' => ReinstateAction::class,
            };

            $mockAction = $this->mock($actionClass, function (MockInterface $mock) use ($exceptionClass) {
                $mock->shouldReceive('handle')
                    ->andThrow(new $exceptionClass('Action failed'));
            });

            Gate::shouldReceive('authorize')->andReturn(true);

            $component = Livewire::test(Actions::class, ['title' => $this->title])
                ->call($action);

            $component->assertNotDispatched('title-updated');
        }
    });
});

describe('Title Actions Session Messages', function () {
    it('sets success messages for successful actions', function () {
        $actions = [
            'debut' => 'Title successfully debuted.',
            'retire' => 'Title successfully retired.',
            'unretire' => 'Title successfully unretired.',
            'deactivate' => 'Title successfully pulled.',
            'reinstate' => 'Title successfully reinstated.',
            'restore' => 'Title successfully restored.',
        ];

        foreach ($actions as $action => $message) {
            $actionClass = match ($action) {
                'debut' => DebutAction::class,
                'retire' => RetireAction::class,
                'unretire' => UnretireAction::class,
                'deactivate' => PullAction::class,
                'reinstate' => ReinstateAction::class,
                'restore' => RestoreAction::class,
            };

            $mockAction = $this->mock($actionClass, function (MockInterface $mock) {
                $mock->shouldReceive('handle');
            });

            Gate::shouldReceive('authorize')->andReturn(true);

            $component = Livewire::test(Actions::class, ['title' => $this->title])
                ->call($action);

            $component->assertSessionHas('status', $message);
        }
    });

    it('sets error messages for failed actions', function () {
        $actions = [
            'debut' => CannotBeDebutedException::class,
            'retire' => CannotBeRetiredException::class,
            'unretire' => CannotBeUnretiredException::class,
            'deactivate' => CannotBePulledException::class,
            'reinstate' => CannotBeReinstatedException::class,
        ];

        foreach ($actions as $action => $exceptionClass) {
            $actionClass = match ($action) {
                'debut' => DebutAction::class,
                'retire' => RetireAction::class,
                'unretire' => UnretireAction::class,
                'deactivate' => PullAction::class,
                'reinstate' => ReinstateAction::class,
            };

            $errorMessage = "Cannot perform {$action} action";
            
            $mockAction = $this->mock($actionClass, function (MockInterface $mock) use ($exceptionClass, $errorMessage) {
                $mock->shouldReceive('handle')
                    ->andThrow(new $exceptionClass($errorMessage));
            });

            Gate::shouldReceive('authorize')->andReturn(true);

            $component = Livewire::test(Actions::class, ['title' => $this->title])
                ->call($action);

            $component->assertSessionHas('error', $errorMessage);
        }
    });
});