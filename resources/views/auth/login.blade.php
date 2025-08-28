<x-layouts.auth>
    <form class="flex flex-col gap-5 p-10" method="post" action="{{ route('login') }}">
        @csrf
        
        <!-- Header -->
        <div class="text-center mb-2.5">
            <h3 class="text-lg font-medium text-gray-900 leading-none mb-2.5">
                Sign in
            </h3>
            <div class="flex items-center justify-center font-medium">
                <span class="text-sm text-gray-600 me-1.5">
                    Need an account?
                </span>
                <a class="text-sm text-primary hover:text-primary-active font-medium" href="{{ route('register') }}">
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

        <!-- Email Field - Shorthand Usage (Flux pattern) -->
        <x-form.input 
            type="email"
            name="email" 
            label="Email"
            placeholder="email@email.com"
            value="{{ old('email') }}" />

        <!-- Password Field - Verbose mode for custom layout -->
        <x-form.field>
            <div class="flex items-center justify-between gap-1">
                <x-form.label for="password">Password</x-form.label>
                <a class="text-sm text-primary hover:text-primary-active font-medium shrink-0" href="{{ route('password.request') }}">
                    Forgot Password?
                </a>
            </div>
            
            <div data-form-control>
                <x-form.input 
                    type="password"
                    name="password"
                    placeholder="Enter Password" />
            </div>
            
            <x-form.error name="password" data-form-error />
        </x-form.field>

        <!-- Remember Me -->
        <label class="flex items-center">
            <input class="rounded border-gray-300 text-primary shadow-sm focus:ring-primary" name="remember" type="checkbox" value="1">
            <span class="ms-2 text-sm text-gray-600">Remember me</span>
        </label>

        <!-- Submit Button -->
        <x-ui.button variant="primary" class="flex justify-center grow">
            Sign In
        </x-ui.button>
    </form>
</x-layouts.auth>
