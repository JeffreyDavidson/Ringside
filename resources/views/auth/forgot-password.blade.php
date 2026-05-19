<x-layouts.auth>
    <form class="flex flex-col gap-5 p-10" method="post" action="{{ route('password.email') }}">
        @csrf
        
        <!-- Header -->
        <div class="text-center mb-2.5">
            <h3 class="text-lg font-medium text-gray-900 leading-none mb-2.5">
                Forgot Password?
            </h3>
            <div class="text-sm text-gray-600">
                Enter your email address and we'll send you a password reset link.
            </div>
        </div>

        <!-- Session Status -->
        @if (session('status'))
            <div class="font-medium text-sm text-green-600">
                {{ session('status') }}
            </div>
        @endif

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

        <!-- Submit Button -->
        <x-button variant="primary" class="flex justify-center grow">
            Email Password Reset Link
        </x-button>

        <!-- Back to Login -->
        <div class="text-center">
            <a class="text-sm text-primary hover:text-primary-active font-medium" href="{{ route('login') }}">
                Back to Sign In
            </a>
        </div>
    </form>
</x-layouts.auth>