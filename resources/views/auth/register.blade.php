<x-layouts.auth>
    <form class="flex flex-col gap-5 p-10" method="post" action="{{ route('register') }}">
        @csrf

        <!-- Header -->
        <div class="mb-2.5 text-center">
            <h3 class="mb-2.5 text-lg leading-none font-medium text-gray-900">Sign up</h3>
            <div class="flex items-center justify-center font-medium">
                <span class="me-1.5 text-sm text-gray-600"> Already have an account? </span>
                <a class="text-primary hover:text-primary-active text-sm font-medium" href="{{ route('login') }}">
                    Sign in
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

        <div class="flex flex-col gap-1">
            <label for="first_name" class="text-2sm font-normal text-gray-900">First Name</label>
            <input
                class="text-2sm focus:border-primary focus:ring-primary block h-10 w-full appearance-none rounded-md border border-solid border-gray-300 bg-gray-50 px-3 leading-4 font-medium text-gray-700 shadow-none transition-colors outline-none focus:bg-white focus:ring-1"
                placeholder="Enter your first name"
                type="text"
                value="{{ old('first_name') }}"
                name="first_name"
                id="first_name"
                required
            />
            @error('first_name')
                <span class="text-xs leading-4 font-medium text-red-500"> {{ $message }} </span>
            @enderror
        </div>

        <div class="flex flex-col gap-1">
            <label for="last_name" class="text-2sm font-normal text-gray-900">Last Name</label>
            <input
                class="text-2sm focus:border-primary focus:ring-primary block h-10 w-full appearance-none rounded-md border border-solid border-gray-300 bg-gray-50 px-3 leading-4 font-medium text-gray-700 shadow-none transition-colors outline-none focus:bg-white focus:ring-1"
                placeholder="Enter your last name"
                type="text"
                value="{{ old('last_name') }}"
                name="last_name"
                id="last_name"
                required
            />
            @error('last_name')
                <span class="text-xs leading-4 font-medium text-red-500"> {{ $message }} </span>
            @enderror
        </div>

        <!-- Email Field -->
        <div class="flex flex-col gap-1">
            <label class="text-2sm font-normal text-gray-900">Email</label>
            <input
                class="text-2sm focus:border-primary focus:ring-primary block h-10 w-full appearance-none rounded-md border border-solid border-gray-300 bg-gray-50 px-3 leading-4 font-medium text-gray-700 shadow-none transition-colors outline-none focus:bg-white focus:ring-1"
                placeholder="email@email.com"
                type="email"
                value="{{ old('email') }}"
                name="email"
                id="email"
                required
            />
            @error('email')
                <span class="text-xs leading-4 font-medium text-red-500"> {{ $message }} </span>
            @enderror
        </div>

        <!-- Password Field -->
        <div class="flex flex-col gap-1">
            <label class="text-2sm font-normal text-gray-900">Password</label>
            <input
                name="password"
                placeholder="Enter Password"
                type="password"
                class="text-2sm focus:border-primary focus:ring-primary block h-10 w-full appearance-none rounded-md border border-solid border-gray-300 bg-gray-50 px-3 leading-4 font-medium text-gray-700 shadow-none transition-colors outline-none focus:bg-white focus:ring-1"
                required
            />
            @error('password')
                <span class="text-xs leading-4 font-medium text-red-500"> {{ $message }} </span>
            @enderror
        </div>

        <!-- Password Confirmation Field -->
        <div class="flex flex-col gap-1">
            <label class="text-2sm font-normal text-gray-900">Confirm Password</label>
            <input
                name="password_confirmation"
                placeholder="Confirm Password"
                type="password"
                class="text-2sm focus:border-primary focus:ring-primary block h-10 w-full appearance-none rounded-md border border-solid border-gray-300 bg-gray-50 px-3 leading-4 font-medium text-gray-700 shadow-none transition-colors outline-none focus:bg-white focus:ring-1"
                required
            />
        </div>

        <!-- Submit Button -->
        <x-button variant="primary" class="flex grow justify-center"> Sign Up </x-button>
    </form>
</x-layouts.auth>
