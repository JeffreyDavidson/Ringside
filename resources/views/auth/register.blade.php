<x-layouts.auth>
    <form class="flex flex-col gap-5 p-10" method="post" action="{{ route('register') }}">
        @csrf
        
        <!-- Header -->
        <div class="text-center mb-2.5">
            <h3 class="text-lg font-medium text-gray-900 leading-none mb-2.5">
                Sign up
            </h3>
            <div class="flex items-center justify-center font-medium">
                <span class="text-sm text-gray-600 me-1.5">
                    Already have an account?
                </span>
                <a class="text-sm text-primary hover:text-primary-active font-medium" href="{{ route('login') }}">
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

        <!-- Name Field -->
        <div class="flex flex-col gap-1">
            <label class="text-2sm font-normal text-gray-900">Name</label>
            <input 
                class="block w-full appearance-none shadow-none outline-none font-medium text-2sm leading-4 bg-gray-50 rounded-md h-10 px-3 border border-solid border-gray-300 text-gray-700 focus:bg-white focus:border-primary focus:ring-1 focus:ring-primary transition-colors" 
                placeholder="Enter your full name" 
                type="text" 
                value="{{ old('name') }}" 
                name="name"
                id="name"
                required>
            @error('name')
                <span class="font-medium text-xs leading-4 text-red-500">
                    {{ $message }}
                </span>
            @enderror
        </div>

        <!-- Email Field -->
        <div class="flex flex-col gap-1">
            <label class="text-2sm font-normal text-gray-900">Email</label>
            <input 
                class="block w-full appearance-none shadow-none outline-none font-medium text-2sm leading-4 bg-gray-50 rounded-md h-10 px-3 border border-solid border-gray-300 text-gray-700 focus:bg-white focus:border-primary focus:ring-1 focus:ring-primary transition-colors" 
                placeholder="email@email.com" 
                type="email" 
                value="{{ old('email') }}" 
                name="email"
                id="email"
                required>
            @error('email')
                <span class="font-medium text-xs leading-4 text-red-500">
                    {{ $message }}
                </span>
            @enderror
        </div>

        <!-- Password Field -->
        <div class="flex flex-col gap-1">
            <label class="text-2sm font-normal text-gray-900">Password</label>
            <input 
                name="password" 
                placeholder="Enter Password" 
                type="password" 
                class="block w-full appearance-none shadow-none outline-none font-medium text-2sm leading-4 bg-gray-50 rounded-md h-10 px-3 border border-solid border-gray-300 text-gray-700 focus:bg-white focus:border-primary focus:ring-1 focus:ring-primary transition-colors"
                required>
            @error('password')
                <span class="font-medium text-xs leading-4 text-red-500">
                    {{ $message }}
                </span>
            @enderror
        </div>

        <!-- Password Confirmation Field -->
        <div class="flex flex-col gap-1">
            <label class="text-2sm font-normal text-gray-900">Confirm Password</label>
            <input 
                name="password_confirmation" 
                placeholder="Confirm Password" 
                type="password" 
                class="block w-full appearance-none shadow-none outline-none font-medium text-2sm leading-4 bg-gray-50 rounded-md h-10 px-3 border border-solid border-gray-300 text-gray-700 focus:bg-white focus:border-primary focus:ring-1 focus:ring-primary transition-colors"
                required>
        </div>

        <!-- Submit Button -->
        <x-button variant="primary" class="flex justify-center grow">
            Sign Up
        </x-button>
    </form>
</x-layouts.auth>