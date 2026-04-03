<x-layouts.auth>
    <form class="flex flex-col gap-5 p-10" method="post" action="{{ route('login') }}">
        @csrf

        <!-- Header -->
        <div class="text-center mb-2.5">
            <h3 class="text-lg font-medium text-foreground leading-none mb-2.5">
                Sign in
            </h3>
            <div class="flex items-center justify-center font-medium">
                <span class="text-sm text-secondary-foreground me-1.5">
                    Need an account?
                </span>
                <a class="text-sm font-medium" href="{{ route('register') }}">
                    Sign up
                </a>
            </div>
        </div>

        <!-- Social Login Buttons -->
        <div class="grid grid-cols-2 gap-2.5">
            <x-auth.social-login-button provider="google" />
            <x-auth.social-login-button provider="apple" />
        </div>

        <!-- Divider -->
        <x-auth.form-divider />

        <!-- Email Field - Explicit structure for custom label classes -->
        <div class="flex flex-col gap-1">
            <x-form.label for="email" class="font-normal text-[var(--mono)]">Email</x-form.label>

            <x-form.input
                type="email"
                name="email"
                id="email"
                data-test="email"
                placeholder="email@email.com"
                value="{{ old('email') }}" />

            <x-form.error name="email" />
        </div>

        <!-- Password Field - Simplified structure -->
        <div class="flex flex-col gap-1">
            <div class="flex items-center justify-between gap-1">
                <x-form.label for="password" class="font-normal text-[var(--mono)]">Password</x-form.label>
                <a class="text-sm text-primary hover:text-primary-active font-medium shrink-0" href="{{ route('password.request') }}">
                    Forgot Password?
                </a>
            </div>

            <x-form.input
                type="password"
                name="password"
                id="password"
                data-test="password"
                placeholder="Enter Password" />

            <x-form.error name="password" />
        </div>

        <!-- Remember Me -->
        <x-form.inputs.checkbox name="remember" label="Remember me" value="1" size="sm" data-test="remember"/>

        <!-- Submit Button -->
        <x-button type="submit" variant="primary" class="w-full flex justify-center" data-test="sign-in">
            Sign In
        </x-button>
    </form>
</x-layouts.auth>
