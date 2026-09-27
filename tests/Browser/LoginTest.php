<?php

declare(strict_types=1);

use App\Enums\Users\UserStatus;
use App\Models\Users\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Support\Facades\Notification;

test('login screen displays correctly', function () {
    $page = visit(route('login'));

    $page->assertSee('Sign in')
        ->assertPresent('@email')
        ->assertPresent('@password')
        ->assertPresent('@sign-in')
        ->assertSee('Email')
        ->assertSee('Password')
        ->assertAttribute('@email', 'placeholder', 'email@email.com')
        ->assertAttribute('@password', 'placeholder', 'Enter Password');
});

test('user can authenticate successfully', function () {
    // Create administrator user for testing
    $admin = User::factory()->administrator()->create([
        'email' => 'administrator@example.com',
        'password' => 'password',
        'status' => UserStatus::Active,
    ]);

    $page = visit(route('login'));

    $page->type('@email', $admin->email)
        ->type('@password', 'password')
        ->press('@sign-in')
        ->assertScript('window.location.pathname === "/dashboard"')
        ->assertSee('Overview');
});

test('authentication fails with invalid credentials', function () {
    $page = visit(route('login'));

    $page->type('@email', 'nonexistent@example.com')
        ->type('@password', 'wrongpassword')
        ->press('@sign-in')
        ->assertSee('These credentials do not match our records')
        ->assertScript('window.location.pathname === "/login"');
});

test('login form validates required fields', function () {
    $page = visit(route('login'));

    $page->press('@sign-in')
        ->assertSee('The email field is required')
        ->assertSee('The password field is required')
        ->assertScript('window.location.pathname === "/login"');
});

test('login form validates email format', function () {
    $page = visit(route('login'));

    $page->type('@email', 'invalid-email-format')
        ->type('@password', 'somepassword')
        ->press('@sign-in')
        ->assertScript('window.location.pathname === "/login"');
});

test('login form has proper accessibility', function () {
    $page = visit(route('login'));

    $page->assertAttribute('@email', 'type', 'email')
        ->assertAttribute('@password', 'type', 'password')
        ->assertSee('Email')
        ->assertSee('Password');
});

test('login form keyboard navigation works', function (): void {
    // Arrange
    $page = visit(route('login'));

    // Act
    $page->click('@email')
        ->keys('@email', 'Tab');

    // Assert
    $page->assertScript('document.activeElement.textContent.trim() === "Forgot password?"');

    // Act
    $page->keys('a[href="'.route('password.request').'"]', 'Tab');

    // Assert
    $page->assertScript('document.activeElement.getAttribute("data-test") === "password"');

    // Act
    $page->keys('@password', 'Tab');

    // Assert
    $page->assertScript('document.activeElement.getAttribute("aria-label") === "Show password"');

    // Act
    $page->keys('button[aria-label="Show password"]', 'Tab');

    // Assert
    $page->assertScript('document.activeElement.getAttribute("data-test") === "remember"');

    // Act
    $page->keys('@remember', 'Tab');

    // Assert
    $page->assertScript('document.activeElement.getAttribute("data-test") === "sign-in"');
});

test('remember me can be selected during login', function (): void {
    // Create administrator user for testing
    $admin = User::factory()->administrator()->create([
        'email' => 'administrator@example.com',
        'password' => 'password',
        'status' => UserStatus::Active,
    ]);

    $page = visit(route('login'));

    $page->type('@email', $admin->email)
        ->type('@password', 'password')
        ->assertPresent('@remember')
        ->check('@remember')
        ->assertChecked('@remember')
        ->press('@sign-in')
        ->assertScript('window.location.pathname === "/dashboard"')
        ->assertSee('Overview');
});

test('login form works on mobile viewports', function () {
    // Create administrator user for testing
    $admin = User::factory()->administrator()->create([
        'email' => 'administrator@example.com',
        'password' => 'password',
        'status' => UserStatus::Active,
    ]);

    $page = visit(route('login'))->on()->mobile();

    $page->assertPresent('@email')
        ->assertPresent('@password')
        ->assertPresent('@sign-in')
        ->type('@email', $admin->email)
        ->type('@password', 'password')
        ->press('@sign-in')
        ->assertScript('window.location.pathname === "/dashboard"')
        ->assertSee('Overview');
});

test('user can logout successfully', function () {
    // Create administrator user for testing
    $admin = User::factory()->administrator()->create([
        'email' => 'administrator@example.com',
        'password' => 'password',
        'status' => UserStatus::Active,
    ]);

    // First login
    $page = visit(route('login'));

    $page->type('@email', $admin->email)
        ->type('@password', 'password')
        ->press('@sign-in')
        ->assertScript('window.location.pathname === "/dashboard"')
        ->assertSee('Overview')
        ->click('@profile-menu')
        ->press('Log out')
        ->assertScript('window.location.pathname === "/login"')
        ->assertSee('Sign in');

    // Verify we can't access protected pages
    $page = visit(route('dashboard'));

    $page->assertScript('window.location.pathname === "/login"')
        ->assertSee('Sign in');
});

test('authenticated users are redirected away from login page', function () {
    $user = User::factory()->create(['status' => UserStatus::Active]);

    $this->actingAs($user);

    $page = visit(route('login'));

    $page->assertScript('window.location.pathname === "/dashboard"')
        ->assertSee('Overview');
});

test('login form handles longer processing times', function () {
    // Create administrator user for testing
    $admin = User::factory()->administrator()->create([
        'email' => 'administrator@example.com',
        'password' => 'password',
        'status' => UserStatus::Active,
    ]);

    $page = visit(route('login'));

    $page->type('@email', $admin->email)
        ->type('@password', 'password')
        ->press('@sign-in')
        ->assertScript('window.location.pathname === "/dashboard"')
        ->assertSee('Overview');
});

test('password recovery and reset work through the branded forms', function (): void {
    // Arrange
    Notification::fake();
    $user = User::factory()->administrator()->create(['status' => UserStatus::Active]);
    $page = visit(route('login'));

    // Act
    $page->click('Forgot password?')
        ->type('email', $user->email)
        ->press('Email password reset link');

    // Assert
    $page->assertSee('Check your email')
        ->assertSee($user->email);
    Notification::assertSentTo($user, ResetPassword::class, function (ResetPassword $notification) use ($user): bool {
        // Arrange
        $page = visit(route('password.reset', ['token' => $notification->token, 'email' => $user->email]));

        // Act
        $page->type('password', 'new-password')
            ->type('password_confirmation', 'new-password')
            ->press('button[type="submit"]');

        // Assert
        $page->assertPathIs('/login')
            ->assertSee('Your password has been reset');

        // Act
        $page->type('@email', $user->email)
            ->type('@password', 'new-password')
            ->press('@sign-in');

        // Assert
        $page->assertPathIs('/dashboard');

        return true;
    });
});

test('registration submits from the branded form', function (): void {
    // Arrange
    $page = visit(route('register'));

    // Act
    $page->type('first_name', 'Taylor')
        ->type('last_name', 'Promoter')
        ->type('email', 'taylor@example.com')
        ->type('password', 'test-password')
        ->type('password_confirmation', 'test-password')
        ->press('Create an account');

    // Assert
    $page->assertPathIs('/login')
        ->assertSee(__('auth-forms.account_pending'));
    $this->assertGuest();
    $this->assertDatabaseHas('users', ['email' => 'taylor@example.com']);
});

test('login exposes password visibility and associates validation errors', function (): void {
    // Arrange
    $page = visit(route('login'));

    // Act
    $page->click('button[aria-label="Show password"]');

    // Assert
    $page->assertAttribute('@password', 'type', 'text')
        ->assertAttribute('@email', 'autocomplete', 'username')
        ->assertAttribute('@password', 'autocomplete', 'current-password');

    // Act
    $page->click('button[aria-label="Hide password"]')
        ->press('@sign-in');

    // Assert
    $page->assertAttribute('@password', 'type', 'password')
        ->assertAttribute('@email', 'aria-invalid', 'true')
        ->assertAttribute('@email', 'aria-describedby', 'email-error')
        ->assertAttribute('@password', 'aria-describedby', 'password-error')
        ->assertSee('The email field is required')
        ->assertSee('The password field is required')
        ->assertNoAccessibilityIssues()
        ->assertNoJavaScriptErrors();
});

test('registration explains passwords and focuses rejected input', function (): void {
    // Arrange
    $page = visit(route('register'));

    // Assert
    $page->assertSee('Use at least 8 characters.')
        ->assertAttribute('#password', 'aria-describedby', 'password-hint')
        ->assertAttribute('button[aria-controls="password_confirmation"]', 'aria-label', 'Show password confirmation');

    // Act
    $page->click('button[aria-controls="password_confirmation"]');

    // Assert
    $page->assertAttribute('#password_confirmation', 'type', 'text')
        ->assertAttribute('#password', 'type', 'password')
        ->assertAttribute('button[aria-controls="password_confirmation"]', 'aria-label', 'Hide password confirmation');

    // Act
    $page->type('first_name', 'Taylor')
        ->type('last_name', 'Promoter')
        ->type('email', 'Taylor@Example.com')
        ->type('password', 'short')
        ->type('password_confirmation', 'short')
        ->press('button[type="submit"]');

    // Assert
    $page->assertSee('The password field must be at least 8 characters')
        ->assertScript('document.activeElement.id === "password"')
        ->assertAttribute('#password', 'aria-describedby', 'password-hint password-error')
        ->assertAttribute('#email', 'value', 'Taylor@Example.com')
        ->assertNoAccessibilityIssues()
        ->assertNoJavaScriptErrors();
});

test('recovery confirmation prevents resending until the cooldown ends', function (): void {
    // Arrange
    Notification::fake();
    $user = User::factory()->create();
    $page = visit(route('password.request'));

    // Act
    $page->type('email', $user->email)
        ->press('button[type="submit"]');

    // Assert
    $page->assertSee('Check your email')
        ->assertSee($user->email)
        ->assertSee('Check your inbox and spam folder')
        ->assertSee('You can request another link in')
        ->assertDisabled('button[type="submit"]');
    Notification::assertSentToTimes($user, ResetPassword::class, 1);

    // Act
    $page->script('window.dispatchEvent(new PageTransitionEvent("pageshow", { persisted: true }))');

    // Assert
    $page->assertDisabled('button[type="submit"]')
        ->assertNoAccessibilityIssues();
    Notification::assertSentToTimes($user, ResetPassword::class, 1);

    // Arrange
    $this->travel(61)->seconds();

    // Act
    $page->script('const actualNow = Date.now; Date.now = () => actualNow() + 61000; window.dispatchEvent(new PageTransitionEvent("pageshow", { persisted: true }));');

    // Assert
    $page->assertEnabled('button[type="submit"]')
        ->assertSee('You can request another link now.');

    // Act
    $page->press('Resend reset link');

    // Assert
    $page->assertSee('Check your email')
        ->assertSee($user->email)
        ->assertDisabled('button[type="submit"]');
    Notification::assertSentToTimes($user, ResetPassword::class, 2);

    // Act
    $page->click('Change email address');

    // Assert
    $page->assertSee('Forgot password?')
        ->assertPresent('#email')
        ->assertDontSee('Check your email')
        ->assertNoJavaScriptErrors();
});

test('auth forms show submission progress and recover when restored', function (string $routeName, string $pendingLabel) {
    // Arrange
    $page = visit(route($routeName));
    $page->type('email', 'promoter@example.com');

    if ($routeName === 'register') {
        $page->type('first_name', 'Taylor')
            ->type('last_name', 'Promoter')
            ->type('password', 'test-password')
            ->type('password_confirmation', 'test-password');
    }

    if ($routeName === 'login') {
        $page->type('password', 'test-password');
    }

    $page->script(<<<'JS'
        window.acceptedSubmissions = 0;
        window.originalSubmitLabel = document.querySelector('button[type="submit"]').textContent;
        document.addEventListener('submit', (event) => {
            if (!event.defaultPrevented) window.acceptedSubmissions++;
            event.preventDefault();
        });
        JS);

    // Act
    $page->press('button[type="submit"]');

    // Assert
    $page->assertSee($pendingLabel)
        ->assertDisabled('button[type="submit"]')
        ->assertAttribute('form[method="post"]', 'aria-busy', 'true');

    // Act
    $page->script('document.querySelector("form").requestSubmit()');

    // Assert
    $page->assertScript('window.acceptedSubmissions === 1');

    // Act
    $page->script('window.dispatchEvent(new PageTransitionEvent("pageshow", { persisted: true }))');

    // Assert
    $page->assertEnabled('button[type="submit"]')
        ->assertScript('document.querySelector("button[type=submit]").textContent === window.originalSubmitLabel')
        ->assertScript('!document.querySelector("form").hasAttribute("aria-busy")')
        ->assertNoJavaScriptErrors();
})->with([
    'login' => ['login', 'Signing in…'],
    'register' => ['register', 'Creating account…'],
    'recovery' => ['password.request', 'Sending link…'],
]);

test('native validation leaves the recovery submit button usable', function (): void {
    // Arrange
    $page = visit(route('password.request'));

    // Act
    $page->press('button[type="submit"]');

    // Assert
    $page->assertEnabled('button[type="submit"]')
        ->assertSee('Email password reset link')
        ->assertScript('document.activeElement.id === "email"')
        ->assertScript('!document.querySelector("form").hasAttribute("aria-busy")');
});
