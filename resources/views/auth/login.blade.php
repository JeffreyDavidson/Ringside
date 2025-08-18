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

        <!-- Email Field -->
        <div class="flex flex-col gap-1">
            <label class="text-2sm font-normal text-gray-900">Email</label>
            <input 
                class="block w-full appearance-none shadow-none outline-none font-medium text-2sm leading-4 bg-gray-50 rounded-md h-10 px-3 border border-solid border-gray-300 text-gray-700 focus:bg-white focus:border-primary focus:ring-1 focus:ring-primary transition-colors" 
                placeholder="email@email.com" 
                type="email" 
                value="{{ old('email') }}" 
                name="email"
                id="email">
            @error('email')
                <span class="font-medium text-xs leading-4 text-red-500">
                    {{ $message }}
                </span>
            @enderror
        </div>

        <!-- Password Field -->
        <div class="flex flex-col gap-1">
            <div class="flex items-center justify-between gap-1">
                <label class="text-2sm font-normal text-gray-900">Password</label>
                <a class="text-sm text-primary hover:text-primary-active font-medium shrink-0" href="{{ route('password.request') }}">
                    Forgot Password?
                </a>
            </div>
            <input 
                name="password" 
                placeholder="Enter Password" 
                type="password" 
                class="block w-full appearance-none shadow-none outline-none font-medium text-2sm leading-4 bg-gray-50 rounded-md h-10 px-3 border border-solid border-gray-300 text-gray-700 focus:bg-white focus:border-primary focus:ring-1 focus:ring-primary transition-colors">
            @error('password')
                <span class="font-medium text-xs leading-4 text-red-500">
                    {{ $message }}
                </span>
            @enderror
        </div>

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
