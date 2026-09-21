@php
    $oldEmail = old('email');
@endphp

<x-layouts.auth>
    <form class="auth-form" method="post" action="{{ route('login') }}">
        @csrf

        <div class="auth-form-header">
            <h2>Sign in</h2>
            <div>
                <span>New to Ringside?</span>
                <a href="{{ route('register') }}">Create an account</a>
            </div>
        </div>

        <div class="auth-field">
            <x-form.label for="email">Email address</x-form.label>

            <x-form.input
                type="email"
                name="email"
                id="email"
                data-test="email"
                placeholder="email@email.com"
                value="{{ is_string($oldEmail) ? $oldEmail : '' }}"
            />

            <x-form.error name="email" />
        </div>

        <div class="auth-field">
            <div class="auth-field-label">
                <x-form.label for="password">Password</x-form.label>
                <a href="{{ route('password.request') }}">Forgot password?</a>
            </div>

            <x-form.input
                type="password"
                name="password"
                id="password"
                data-test="password"
                placeholder="Enter Password"
            />

            <x-form.error name="password" />
        </div>

        <x-form.inputs.checkbox name="remember" label="Remember me" value="1" size="sm" data-test="remember" />

        <x-button type="submit" variant="primary" class="auth-submit" data-test="sign-in">
            Sign in to Ringside
        </x-button>
    </form>
</x-layouts.auth>
