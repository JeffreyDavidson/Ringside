# Laravel Dusk v8.3.3 - CSS Selector Scoping Bug

## Bug Summary
Laravel Dusk v8.3.3 incorrectly prepends `body ` to all CSS selectors in `assertPresent()` and related methods, causing element selection to fail even for basic HTML elements.

## Environment
- **Laravel Dusk Version**: v8.3.3
- **Laravel Version**: 12.21.0
- **PHP Version**: 8.4.11
- **PHPUnit Version**: 11.5.15
- **ChromeDriver Version**: 138.0.7204.183
- **Operating System**: macOS (Darwin 24.6.0)

## Expected Behavior
```php
$browser->assertPresent('body'); // Should find <body> element
$browser->assertPresent('html'); // Should find <html> element
```

## Actual Behavior
```php
$browser->assertPresent('body'); // Searches for "body body" - fails
$browser->assertPresent('html'); // Searches for "body html" - fails
```

**Error Messages:**
```
Element [body body] is not present.
Element [body html] is not present.
```

## Reproduction Steps

### 1. Basic Test Setup
```php
<?php

declare(strict_types=1);

use Laravel\Dusk\Browser;

test('demonstrate selector scoping bug', function () {
    $this->browse(function (Browser $browser) {
        $browser->visit('http://example.com');  // Any valid page
        
        // These should work but fail with "body body" error
        $browser->assertPresent('body');  // FAILS
        $browser->assertPresent('html');  // FAILS
    });
});
```

### 2. Diagnostic Test Results
```php
// Our diagnostic test revealed:
echo "assertPresent('html'): FAILED - Element [body html] is not present.\n";
echo "assertPresent('body'): FAILED - Element [body body] is not present.\n";

// But WebDriver direct access works perfectly:
$elements = $browser->driver->findElements(\Facebook\WebDriver\WebDriverBy::tagName('body'));
echo "WebDriver direct findElements('body'): " . count($elements) . " elements found\n";
// Output: "WebDriver direct findElements('body'): 1 elements found"
```

## Root Cause Analysis
The issue appears to be in Dusk's CSS selector processing where it automatically prepends `body ` to selectors for scoping, but this breaks when:

1. The selector itself is `body` (becomes `body body`)
2. The selector is `html` (becomes `body html`) 
3. Any other root-level element selector

## Workaround
Direct WebDriver calls work correctly:

```php
// Instead of: $browser->assertPresent('body');
$bodyElements = $browser->driver->findElements(\Facebook\WebDriver\WebDriverBy::tagName('body'));
$hasBody = count($bodyElements) > 0;

// Instead of: $browser->element('input[name="email"]');  
$emailInput = $browser->driver->findElement(\Facebook\WebDriver\WebDriverBy::name('email'));
```

## Impact
This bug makes basic element assertions impossible and breaks fundamental browser testing functionality. Tests that should pass with simple HTML elements fail due to incorrect selector scoping.

## Additional Context
- This appears to be a regression in v8.3.3
- The bug affects all selector-based methods (`assertPresent`, `element`, `text`, etc.)
- WebDriver functionality remains unaffected, suggesting the issue is in Dusk's abstraction layer
- Pages load correctly, and other Dusk functionality (screenshots, navigation) works

## Test Case for Verification
```php
<?php

test('selector scoping bug demonstration', function () {
    $this->browse(function (Browser $browser) {
        $browser->visit('data:text/html,<html><body><h1>Test</h1></body></html>');
        
        // These should all work but currently fail:
        $browser->assertPresent('html');   // Fails: looks for "body html"
        $browser->assertPresent('body');   // Fails: looks for "body body"  
        $browser->assertPresent('h1');     // Should work: looks for "body h1"
        
        // Workaround that works:
        $bodyCount = count($browser->driver->findElements(
            \Facebook\WebDriver\WebDriverBy::tagName('body')
        ));
        expect($bodyCount)->toBe(1);
    });
});
```

---

**This issue completely breaks basic HTML element testing and should be considered high priority.**