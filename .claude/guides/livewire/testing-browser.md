# Browser Testing

## Overview

This guide covers browser testing strategies for Livewire components in Ringside using Laravel Dusk. Browser testing validates component behavior in real browsers, ensuring JavaScript interactions, responsiveness, and user experience work correctly.

## Browser Testing Setup

### Laravel Dusk Configuration

Configure Laravel Dusk for Livewire testing:

```php
// tests/DuskTestCase.php
<?php

namespace Tests;

use Facebook\WebDriver\Chrome\ChromeOptions;
use Facebook\WebDriver\Remote\DesiredCapabilities;
use Facebook\WebDriver\Remote\RemoteWebDriver;
use Laravel\Dusk\TestCase as BaseTestCase;
use Livewire\Livewire;

abstract class DuskTestCase extends BaseTestCase
{
    use CreatesApplication;

    /**
     * Prepare for Dusk test execution.
     */
    public static function prepare(): void
    {
        if (! static::runningInSail()) {
            static::startChromeDriver();
        }
    }

    /**
     * Create the RemoteWebDriver instance.
     */
    protected function driver(): RemoteWebDriver
    {
        $options = (new ChromeOptions)->addArguments([
            '--disable-gpu',
            '--headless',
            '--no-sandbox',
            '--disable-dev-shm-usage',
            '--disable-extensions',
            '--window-size=1920,1080',
        ]);

        return RemoteWebDriver::create(
            $_ENV['DUSK_DRIVER_URL'] ?? 'http://localhost:9515',
            DesiredCapabilities::chrome()->setCapability(
                ChromeOptions::CAPABILITY, $options
            )
        );
    }

    /**
     * Setup the test environment.
     */
    protected function setUp(): void
    {
        parent::setUp();
        
        // Configure for browser testing
        $this->artisan('migrate:fresh');
        $this->seed();
    }
}
```

### Livewire Browser Testing Traits

Create traits for Livewire browser testing:

```php
// tests/Browser/Traits/LivewireBrowserTesting.php
<?php

namespace Tests\Browser\Traits;

use Laravel\Dusk\Browser;

trait LivewireBrowserTesting
{
    /**
     * Set a Livewire property value
     */
    protected function setLivewireProperty(Browser $browser, string $property, $value): Browser
    {
        return $browser->script("
            Livewire.find('{$this->getComponentId($browser)}').set('{$property}', '{$value}');
        ");
    }

    /**
     * Call a Livewire method
     */
    protected function callLivewireMethod(Browser $browser, string $method, ...$parameters): Browser
    {
        $params = json_encode($parameters);
        
        return $browser->script("
            Livewire.find('{$this->getComponentId($browser)}').call('{$method}', ...{$params});
        ");
    }

    /**
     * Wait for Livewire to finish processing
     */
    protected function waitForLivewire(Browser $browser): Browser
    {
        return $browser->waitUntil('!window.Livewire.isLoading()');
    }

    /**
     * Get the component ID from the browser
     */
    protected function getComponentId(Browser $browser): string
    {
        return $browser->script("
            return document.querySelector('[wire\\:id]').getAttribute('wire:id');
        ")[0];
    }

    /**
     * Assert that a Livewire event was dispatched
     */
    protected function assertLivewireEventDispatched(Browser $browser, string $event): Browser
    {
        return $browser->waitUntil("
            window.dispatchedEvents && window.dispatchedEvents.includes('{$event}')
        ");
    }
}
```

## Component Browser Testing

### Table Component Browser Testing

Test table components in browser environment:

```php
// tests/Browser/Components/EventsTableBrowserTest.php
<?php

namespace Tests\Browser\Components;

use App\Models\Events\Event;
use App\Models\Events\Venue;
use App\Models\Users\User;
use Laravel\Dusk\Browser;
use Tests\Browser\Traits\LivewireBrowserTesting;
use Tests\DuskTestCase;

class EventsTableBrowserTest extends DuskTestCase
{
    use LivewireBrowserTesting;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->admin = User::factory()->administrator()->create();
        $this->venues = Venue::factory()->count(5)->create();
        $this->events = Event::factory()->count(15)->create([
            'venue_id' => fn() => $this->venues->random()->id,
        ]);
    }

    public function test_table_displays_events_correctly()
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->admin)
                ->visit('/events')
                ->waitForText('Events')
                ->assertSee($this->events->first()->name)
                ->assertSee($this->events->first()->venue->name);
        });
    }

    public function test_table_search_functionality()
    {
        $searchEvent = $this->events->first();
        
        $this->browse(function (Browser $browser) use ($searchEvent) {
            $browser->loginAs($this->admin)
                ->visit('/events')
                ->waitForText('Events')
                ->type('search', $searchEvent->name)
                ->waitForLivewire($browser)
                ->assertSee($searchEvent->name)
                ->assertDontSee($this->events->last()->name);
        });
    }

    public function test_table_pagination_works()
    {
        // Create more events for pagination
        Event::factory()->count(50)->create([
            'venue_id' => fn() => $this->venues->random()->id,
        ]);

        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->admin)
                ->visit('/events')
                ->waitForText('Events')
                ->assertSee('Next')
                ->click('@pagination-next')
                ->waitForLivewire($browser)
                ->assertSee('Previous');
        });
    }

    public function test_table_sorting_functionality()
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->admin)
                ->visit('/events')
                ->waitForText('Events')
                ->click('@sort-name')
                ->waitForLivewire($browser)
                ->assertSee('↑') // Ascending indicator
                ->click('@sort-name')
                ->waitForLivewire($browser)
                ->assertSee('↓'); // Descending indicator
        });
    }

    public function test_table_filtering_works()
    {
        $activeEvents = Event::factory()->count(5)->create([
            'status' => 'active',
            'venue_id' => fn() => $this->venues->random()->id,
        ]);

        $this->browse(function (Browser $browser) use ($activeEvents) {
            $browser->loginAs($this->admin)
                ->visit('/events')
                ->waitForText('Events')
                ->select('filters.status', 'active')
                ->waitForLivewire($browser)
                ->assertSee($activeEvents->first()->name);
        });
    }

    public function test_table_bulk_actions()
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->admin)
                ->visit('/events')
                ->waitForText('Events')
                ->check('@event-checkbox-' . $this->events->first()->id)
                ->check('@event-checkbox-' . $this->events->last()->id)
                ->click('@bulk-delete')
                ->waitForText('Are you sure')
                ->click('@confirm-bulk-delete')
                ->waitForLivewire($browser)
                ->assertSee('Events deleted successfully');
        });
    }
}
```

### Modal Component Browser Testing

Test modal components in browser environment:

```php
// tests/Browser/Components/EventFormModalBrowserTest.php
<?php

namespace Tests\Browser\Components;

use App\Models\Events\Event;
use App\Models\Events\Venue;
use App\Models\Users\User;
use Laravel\Dusk\Browser;
use Tests\Browser\Traits\LivewireBrowserTesting;
use Tests\DuskTestCase;

class EventFormModalBrowserTest extends DuskTestCase
{
    use LivewireBrowserTesting;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->admin = User::factory()->administrator()->create();
        $this->venue = Venue::factory()->create();
    }

    public function test_modal_opens_and_closes()
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->admin)
                ->visit('/events')
                ->waitForText('Events')
                ->click('@create-event-button')
                ->waitForText('Create Event')
                ->assertVisible('@event-form-modal')
                ->click('@close-modal')
                ->waitUntilMissing('@event-form-modal');
        });
    }

    public function test_modal_form_validation()
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->admin)
                ->visit('/events')
                ->waitForText('Events')
                ->click('@create-event-button')
                ->waitForText('Create Event')
                ->click('@save-event')
                ->waitForLivewire($browser)
                ->assertSee('The name field is required')
                ->assertVisible('@event-form-modal'); // Modal should stay open
        });
    }

    public function test_modal_creates_event_successfully()
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->admin)
                ->visit('/events')
                ->waitForText('Events')
                ->click('@create-event-button')
                ->waitForText('Create Event')
                ->type('@event-name', 'Test Event')
                ->type('@event-date', '2024-12-01')
                ->type('@event-time', '19:00')
                ->select('@event-venue', $this->venue->id)
                ->type('@event-preview', 'This is a test event')
                ->click('@save-event')
                ->waitForLivewire($browser)
                ->waitUntilMissing('@event-form-modal')
                ->assertSee('Event created successfully')
                ->assertSee('Test Event');
        });
    }

    public function test_modal_edits_event_successfully()
    {
        $event = Event::factory()->create([
            'name' => 'Original Event',
            'venue_id' => $this->venue->id,
        ]);

        $this->browse(function (Browser $browser) use ($event) {
            $browser->loginAs($this->admin)
                ->visit('/events')
                ->waitForText('Events')
                ->click('@edit-event-' . $event->id)
                ->waitForText('Edit Event')
                ->clear('@event-name')
                ->type('@event-name', 'Updated Event')
                ->click('@save-event')
                ->waitForLivewire($browser)
                ->waitUntilMissing('@event-form-modal')
                ->assertSee('Event updated successfully')
                ->assertSee('Updated Event');
        });
    }

    public function test_modal_handles_keyboard_interactions()
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->admin)
                ->visit('/events')
                ->waitForText('Events')
                ->click('@create-event-button')
                ->waitForText('Create Event')
                ->keys('@event-name', 'Test Event')
                ->keys('@event-name', ['{tab}'])
                ->keys('', '2024-12-01')
                ->keys('', ['{tab}'])
                ->keys('', '19:00')
                ->keys('', ['{tab}'])
                ->selectFromDropdown('@event-venue', $this->venue->name)
                ->keys('@event-preview', 'This is a test event')
                ->keys('', ['{enter}'])
                ->waitForLivewire($browser)
                ->assertSee('Event created successfully');
        });
    }
}
```

### Form Component Browser Testing

Test form components with complex interactions:

```php
// tests/Browser/Components/EventFormBrowserTest.php
<?php

namespace Tests\Browser\Components;

use App\Models\Events\Event;
use App\Models\Events\Venue;
use App\Models\Users\User;
use Laravel\Dusk\Browser;
use Tests\Browser\Traits\LivewireBrowserTesting;
use Tests\DuskTestCase;

class EventFormBrowserTest extends DuskTestCase
{
    use LivewireBrowserTesting;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->admin = User::factory()->administrator()->create();
        $this->venues = Venue::factory()->count(5)->create();
    }

    public function test_form_real_time_validation()
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->admin)
                ->visit('/events/create')
                ->waitForText('Create Event')
                ->type('@event-name', 'Te')
                ->waitForLivewire($browser)
                ->assertSee('Name must be at least 3 characters')
                ->type('@event-name', 'st Event')
                ->waitForLivewire($browser)
                ->assertDontSee('Name must be at least 3 characters');
        });
    }

    public function test_form_dependent_fields()
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->admin)
                ->visit('/events/create')
                ->waitForText('Create Event')
                ->select('@event-venue', $this->venues->first()->id)
                ->waitForLivewire($browser)
                ->assertSee($this->venues->first()->name)
                ->assertSee($this->venues->first()->city);
        });
    }

    public function test_form_file_upload()
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->admin)
                ->visit('/events/create')
                ->waitForText('Create Event')
                ->attach('@event-poster', __DIR__ . '/../../fixtures/test-poster.jpg')
                ->waitForLivewire($browser)
                ->assertSee('Poster uploaded successfully')
                ->assertVisible('@poster-preview');
        });
    }

    public function test_form_auto_save()
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->admin)
                ->visit('/events/create')
                ->waitForText('Create Event')
                ->type('@event-name', 'Auto Save Test')
                ->pause(2000) // Wait for auto-save
                ->waitForLivewire($browser)
                ->assertSee('Draft saved')
                ->refresh()
                ->waitForText('Create Event')
                ->assertInputValue('@event-name', 'Auto Save Test');
        });
    }

    public function test_form_dummy_data_generation()
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->admin)
                ->visit('/events/create')
                ->waitForText('Create Event')
                ->click('@fill-dummy-data')
                ->waitForLivewire($browser)
                ->assertInputValueIsNot('@event-name', '')
                ->assertInputValueIsNot('@event-preview', '')
                ->assertSelected('@event-venue');
        });
    }

    public function test_form_responsive_behavior()
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->admin)
                ->resize(375, 812) // Mobile size
                ->visit('/events/create')
                ->waitForText('Create Event')
                ->assertVisible('@mobile-form-container')
                ->resize(1024, 768) // Tablet size
                ->waitForLivewire($browser)
                ->assertVisible('@tablet-form-container')
                ->resize(1920, 1080) // Desktop size
                ->waitForLivewire($browser)
                ->assertVisible('@desktop-form-container');
        });
    }
}
```

## JavaScript Integration Testing

### Livewire JavaScript Events

Test Livewire JavaScript event handling:

```php
public function test_livewire_javascript_events()
{
    $this->browse(function (Browser $browser) {
        $browser->loginAs($this->admin)
            ->visit('/events')
            ->waitForText('Events')
            ->script('
                window.dispatchedEvents = [];
                window.addEventListener("event-created", (e) => {
                    window.dispatchedEvents.push("event-created");
                });
            ')
            ->click('@create-event-button')
            ->waitForText('Create Event')
            ->type('@event-name', 'JavaScript Event Test')
            ->select('@event-venue', $this->venue->id)
            ->click('@save-event')
            ->waitForLivewire($browser)
            ->assertScript('window.dispatchedEvents.includes("event-created")', true);
    });
}
```

### Custom JavaScript Interactions

Test custom JavaScript integrated with Livewire:

```php
public function test_custom_javascript_integration()
{
    $this->browse(function (Browser $browser) {
        $browser->loginAs($this->admin)
            ->visit('/events')
            ->waitForText('Events')
            ->script('
                // Custom JavaScript that interacts with Livewire
                window.customEventHandler = function(eventData) {
                    Livewire.emit("custom-event", eventData);
                };
            ')
            ->click('@trigger-custom-event')
            ->waitForLivewire($browser)
            ->assertSee('Custom event handled');
    });
}
```

## Multi-Browser Testing

### Cross-Browser Compatibility

Test components across different browsers:

```php
// tests/Browser/CrossBrowserTest.php
<?php

namespace Tests\Browser;

use App\Models\Events\Event;
use App\Models\Events\Venue;
use App\Models\Users\User;
use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

class CrossBrowserTest extends DuskTestCase
{
    public function test_components_work_in_chrome()
    {
        $this->browse(function (Browser $browser) {
            $browser->driver->getCapabilities()->getBrowserName(); // Chrome
            $this->runBasicComponentTests($browser);
        });
    }

    public function test_components_work_in_firefox()
    {
        $this->browse(function (Browser $browser) {
            // Configure for Firefox
            $this->runBasicComponentTests($browser);
        });
    }

    protected function runBasicComponentTests(Browser $browser)
    {
        $admin = User::factory()->administrator()->create();
        $venue = Venue::factory()->create();
        
        $browser->loginAs($admin)
            ->visit('/events')
            ->waitForText('Events')
            ->click('@create-event-button')
            ->waitForText('Create Event')
            ->type('@event-name', 'Cross Browser Test')
            ->select('@event-venue', $venue->id)
            ->click('@save-event')
            ->waitForLivewire($browser)
            ->assertSee('Event created successfully');
    }
}
```

### Mobile Testing

Test components on mobile devices:

```php
public function test_components_work_on_mobile()
{
    $this->browse(function (Browser $browser) {
        $browser->loginAs($this->admin)
            ->resize(375, 812) // iPhone X size
            ->visit('/events')
            ->waitForText('Events')
            ->assertVisible('@mobile-menu-button')
            ->click('@mobile-menu-button')
            ->waitForText('Create Event')
            ->click('@mobile-create-event')
            ->waitForText('Create Event')
            ->type('@event-name', 'Mobile Test Event')
            ->select('@event-venue', $this->venue->id)
            ->click('@save-event')
            ->waitForLivewire($browser)
            ->assertSee('Event created successfully');
    });
}
```

## Performance Testing in Browser

### Load Time Testing

Test component load times in browser:

```php
public function test_component_load_performance()
{
    Event::factory()->count(100)->create();
    
    $this->browse(function (Browser $browser) {
        $startTime = microtime(true);
        
        $browser->loginAs($this->admin)
            ->visit('/events')
            ->waitForText('Events');
        
        $endTime = microtime(true);
        $loadTime = $endTime - $startTime;
        
        $this->assertLessThan(3.0, $loadTime, 'Page load time should be less than 3 seconds');
    });
}
```

### Memory Usage Testing

Test browser memory usage:

```php
public function test_browser_memory_usage()
{
    $this->browse(function (Browser $browser) {
        $browser->loginAs($this->admin)
            ->visit('/events')
            ->waitForText('Events');
        
        // Check memory usage
        $memoryUsage = $browser->script('
            return performance.memory ? performance.memory.usedJSHeapSize : 0;
        ')[0];
        
        $this->assertLessThan(50 * 1024 * 1024, $memoryUsage, 'Memory usage should be less than 50MB');
    });
}
```

## Accessibility Testing

### Screen Reader Testing

Test components with screen reader compatibility:

```php
public function test_screen_reader_compatibility()
{
    $this->browse(function (Browser $browser) {
        $browser->loginAs($this->admin)
            ->visit('/events')
            ->waitForText('Events')
            ->assertAttribute('@events-table', 'role', 'table')
            ->assertAttribute('@table-header', 'role', 'columnheader')
            ->assertPresent('@table-caption')
            ->assertAttribute('@create-event-button', 'aria-label', 'Create new event');
    });
}
```

### Keyboard Navigation Testing

Test keyboard navigation:

```php
public function test_keyboard_navigation()
{
    $this->browse(function (Browser $browser) {
        $browser->loginAs($this->admin)
            ->visit('/events')
            ->waitForText('Events')
            ->keys('', ['{tab}']) // Tab to first focusable element
            ->keys('', ['{enter}']) // Press enter
            ->waitForText('Create Event')
            ->keys('', ['{escape}']) // Press escape
            ->waitUntilMissing('@event-form-modal');
    });
}
```

## Visual Testing

### Screenshot Testing

Test component visual appearance:

```php
public function test_component_visual_appearance()
{
    $this->browse(function (Browser $browser) {
        $browser->loginAs($this->admin)
            ->visit('/events')
            ->waitForText('Events')
            ->screenshot('events-table-default')
            ->click('@create-event-button')
            ->waitForText('Create Event')
            ->screenshot('event-form-modal-open');
    });
}
```

### Visual Regression Testing

Test for visual regressions:

```php
public function test_visual_regression()
{
    $this->browse(function (Browser $browser) {
        $browser->loginAs($this->admin)
            ->visit('/events')
            ->waitForText('Events')
            ->assertScreenshot('events-table-baseline');
    });
}
```

## Integration Testing

### End-to-End User Workflows

Test complete user workflows:

```php
public function test_complete_event_management_workflow()
{
    $this->browse(function (Browser $browser) {
        $browser->loginAs($this->admin)
            ->visit('/events')
            ->waitForText('Events')
            
            // Create event
            ->click('@create-event-button')
            ->waitForText('Create Event')
            ->type('@event-name', 'Complete Workflow Test')
            ->select('@event-venue', $this->venue->id)
            ->click('@save-event')
            ->waitForLivewire($browser)
            ->assertSee('Event created successfully')
            
            // Edit event
            ->click('@edit-event-1')
            ->waitForText('Edit Event')
            ->clear('@event-name')
            ->type('@event-name', 'Updated Workflow Test')
            ->click('@save-event')
            ->waitForLivewire($browser)
            ->assertSee('Event updated successfully')
            
            // Delete event
            ->click('@delete-event-1')
            ->waitForText('Are you sure')
            ->click('@confirm-delete')
            ->waitForLivewire($browser)
            ->assertSee('Event deleted successfully');
    });
}
```

## Browser Testing Utilities

### Browser Test Helpers

Create utilities for browser testing:

```php
// tests/Browser/Traits/BrowserTestHelpers.php
<?php

namespace Tests\Browser\Traits;

use Laravel\Dusk\Browser;

trait BrowserTestHelpers
{
    protected function fillEventForm(Browser $browser, array $data = []): Browser
    {
        $defaultData = [
            'name' => 'Test Event',
            'date' => '2024-12-01',
            'time' => '19:00',
            'venue_id' => $this->venue->id,
            'preview' => 'Test event preview',
        ];
        
        $data = array_merge($defaultData, $data);
        
        return $browser->type('@event-name', $data['name'])
            ->type('@event-date', $data['date'])
            ->type('@event-time', $data['time'])
            ->select('@event-venue', $data['venue_id'])
            ->type('@event-preview', $data['preview']);
    }
    
    protected function assertNotificationShown(Browser $browser, string $message): Browser
    {
        return $browser->waitForText($message)
            ->assertVisible('@notification-toast');
    }
    
    protected function waitForModalToClose(Browser $browser): Browser
    {
        return $browser->waitUntilMissing('@event-form-modal');
    }
    
    protected function assertTableRowExists(Browser $browser, string $identifier): Browser
    {
        return $browser->assertVisible("@table-row-{$identifier}");
    }
    
    protected function assertTableRowMissing(Browser $browser, string $identifier): Browser
    {
        return $browser->assertMissing("@table-row-{$identifier}");
    }
}
```

### Browser Test Environment

Configure browser test environment:

```php
// tests/Browser/BrowserTestEnvironment.php
<?php

namespace Tests\Browser;

use App\Models\Events\Venue;
use App\Models\Users\User;

class BrowserTestEnvironment
{
    public static function setupBasicData(): array
    {
        $admin = User::factory()->administrator()->create([
            'email' => 'admin@test.com',
            'password' => bcrypt('password'),
        ]);
        
        $venues = Venue::factory()->count(10)->create();
        
        return compact('admin', 'venues');
    }
    
    public static function setupLargeDataset(): array
    {
        $data = self::setupBasicData();
        
        Event::factory()->count(100)->create([
            'venue_id' => fn() => $data['venues']->random()->id,
        ]);
        
        return $data;
    }
    
    public static function setupDevelopmentData(): array
    {
        // Set up realistic development data
        $data = self::setupBasicData();
        
        Event::factory()->count(25)->create([
            'venue_id' => fn() => $data['venues']->random()->id,
        ]);
        
        return $data;
    }
}
```

## Best Practices

### Browser Test Organization
- Use page objects for complex interactions
- Group related tests in logical classes
- Use meaningful test names and descriptions
- Test both happy path and error scenarios

### Performance Considerations
- Use headless browsers for faster execution
- Minimize browser resizing during tests
- Use efficient selectors
- Clean up after each test

### Reliability
- Use proper wait strategies
- Handle async operations correctly
- Test in realistic environments
- Use stable selectors

### Maintenance
- Keep tests independent
- Use reusable helpers and utilities
- Update tests when UI changes
- Monitor test execution times

## Related Documentation

- [Testing Guide](testing-guide.md) - Main testing overview
- [Best Practices](testing-best-practices.md) - Testing best practices
- [Component Architecture](../../architecture/livewire/component-architecture.md) - Overall architecture
- [Performance Testing](testing-performance.md) - Performance testing strategies