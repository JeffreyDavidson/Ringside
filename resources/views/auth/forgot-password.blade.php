<x-layouts.auth>
    <form class="flex flex-col gap-5 p-10" method="post" action="{{ route('password.email') }}">
        @csrf

        <!-- Header -->
        <div class="mb-2.5 text-center">
            <h3 class="mb-2.5 text-lg leading-none font-medium text-gray-900">Forgot Password?</h3>
            <div class="text-sm text-gray-600">Enter your email address and we'll send you a password reset link.</div>
        </div>

        <!-- Session Status -->
        @if (session('status'))
            <div class="text-sm font-medium text-green-600">{{ session('status') }}</div>
        @endif

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

        <!-- Submit Button -->
        <x-button variant="primary" class="flex grow justify-center"> Email Password Reset Link </x-button>

        <!-- Back to Login -->
        <div class="text-center">
            <a class="text-primary hover:text-primary-active text-sm font-medium" href="{{ route('login') }}">
                Back to Sign In
            </a>
        </div>
    </form>
</x-layouts.auth>
