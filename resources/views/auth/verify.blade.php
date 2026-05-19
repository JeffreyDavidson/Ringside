<x-layouts.auth>
    <div class="w-full">
        <h1 class="text-lg font-semibold text-gray-900 mb-6 text-center">{{ __('Verify Your Email Address') }}</h1>
        
        @if (session('resent'))
            <div class="bg-success-light border border-success text-success-700 px-4 py-3 rounded mb-4">
                {{ __('A fresh verification link has been sent to your email address.') }}
            </div>
        @endif

        <div class="text-gray-600 text-sm text-center space-y-2">
            <p>{{ __('Before proceeding, please check your email for a verification link.') }}</p>
            <p>
                {{ __('If you did not receive the email') }}, 
                <a href="{{ route('verification.resend') }}" class="text-primary hover:text-primary-active underline">
                    {{ __('click here to request another') }}
                </a>.
            </p>
        </div>
    </div>
</x-layouts.auth>
