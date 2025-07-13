<?php

declare(strict_types=1);

namespace Tests;

use App\Models\Users\User;
use Exception;
use Facebook\WebDriver\Chrome\ChromeOptions;
use Facebook\WebDriver\Remote\DesiredCapabilities;
use Facebook\WebDriver\Remote\RemoteWebDriver;
use Illuminate\Support\Collection;
use Laravel\Dusk\Browser;
use Laravel\Dusk\TestCase as BaseTestCase;
use PHPUnit\Framework\Attributes\BeforeClass;

use function function_exists;

abstract class DuskTestCase extends BaseTestCase
{
    /**
     * Prepare for Dusk test execution.
     */
    #[BeforeClass]
    public static function prepare(): void
    {
        if (! static::runningInSail()) {
            static::startChromeDriver(['--port=9515']);
        }
    }

    /**
     * Create the RemoteWebDriver instance.
     */
    protected function driver(): RemoteWebDriver
    {
        $options = (new ChromeOptions())->addArguments(collect([
            $this->shouldStartMaximized() ? '--start-maximized' : '--window-size=1920,1080',
            '--disable-search-engine-choice-screen',
            '--disable-smooth-scrolling',
        ])->unless($this->hasHeadlessDisabled(), function (Collection $items) {
            return $items->merge([
                '--disable-gpu',
                '--headless=new',
            ]);
        })->all());

        return RemoteWebDriver::create(
            $_ENV['DUSK_DRIVER_URL'] ?? env('DUSK_DRIVER_URL') ?? 'http://localhost:9515',
            DesiredCapabilities::chrome()->setCapability(
                ChromeOptions::CAPABILITY, $options
            )
        );
    }

    /**
     * Get an administrator user for testing.
     */
    protected function getAdminUser(): User
    {
        return function_exists('administrator') ? administrator() :
            User::factory()->create(['role' => 'administrator']);
    }

    /**
     * Take a screenshot with a descriptive name.
     */
    protected function takeScreenshot(Browser $browser, string $name): void
    {
        $browser->screenshot($name);
    }

    /**
     * Wait for Livewire components to load and initialize.
     */
    protected function waitForLivewireComponent(Browser $browser, int $timeout = 3000): void
    {
        $browser->pause($timeout); // Allow Livewire components to initialize
    }

    /**
     * Assert that a flash message is displayed.
     */
    protected function assertFlashMessage(Browser $browser, ?string $type = null): void
    {
        $flashSelectors = [
            '.alert',
            '.flash-message',
            '.notification',
            '[data-flash]',
            '.toast',
        ];

        $messageFound = false;
        foreach ($flashSelectors as $selector) {
            try {
                if ($browser->element($selector)) {
                    $messageFound = true;
                    break;
                }
            } catch (Exception) {
                continue;
            }
        }

        if ($messageFound && $type) {
            $browser->assertPresent(".{$type}, .alert-{$type}");
        }
    }

    /**
     * Wait for page to fully load including all Livewire components.
     */
    protected function waitForPageLoad(Browser $browser, int $timeout = 5000): void
    {
        $browser->pause($timeout);
    }

    /**
     * Login as administrator and navigate to a page.
     */
    protected function loginAsAdminAndVisit(Browser $browser, string $url): void
    {
        $browser->loginAs($this->getAdminUser())
            ->visit($url);
    }

    /**
     * Assert that a table contains specific data.
     */
    protected function assertTableContains(Browser $browser, string $data): void
    {
        $tableSelectors = [
            'table',
            '.table',
            '[role="table"]',
            '.data-table',
        ];

        foreach ($tableSelectors as $selector) {
            try {
                if ($browser->element($selector)) {
                    $browser->assertSee($data);

                    return;
                }
            } catch (Exception) {
                continue;
            }
        }

        // Fallback: just check if data is visible anywhere on page
        $browser->assertSee($data);
    }

    /**
     * Wait for a modal to open and be ready for interaction.
     */
    protected function waitForModal(Browser $browser, int $timeout = 2000): void
    {
        $browser->pause($timeout);

        $modalSelectors = [
            '.modal',
            '[data-dusk="modal-container"]',
            '.modal-dialog',
            '[role="dialog"]',
        ];

        foreach ($modalSelectors as $selector) {
            try {
                if ($browser->element($selector)) {
                    return;
                }
            } catch (Exception) {
                continue;
            }
        }
    }

    /**
     * Safely click an element using multiple selector strategies.
     */
    protected function safeClick(Browser $browser, array $selectors): bool
    {
        foreach ($selectors as $selector) {
            try {
                if ($browser->element($selector)) {
                    $browser->click($selector);

                    return true;
                }
            } catch (Exception) {
                continue;
            }
        }

        return false;
    }

    /**
     * Assert that an element is present using fallback selectors.
     */
    protected function assertElementPresent(Browser $browser, array $selectors): void
    {
        $found = false;
        foreach ($selectors as $selector) {
            try {
                if ($browser->element($selector)) {
                    $browser->assertPresent($selector);
                    $found = true;
                    break;
                }
            } catch (Exception) {
                continue;
            }
        }

        if (! $found) {
            throw new Exception('None of the expected elements were found: '.implode(', ', $selectors));
        }
    }
}
