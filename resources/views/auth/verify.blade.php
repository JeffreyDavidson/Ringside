<x-layouts.auth>
    <div class="w-full">
        <h1 class="mb-6 text-center text-lg font-semibold text-gray-900">{{ __('Verify Your Email Address') }}</h1>

        @if (session('resent'))
            <div class="bg-success-light border-success text-success-700 mb-4 rounded border px-4 py-3">
                {{ __('A fresh verification link has been sent to your email address.') }}
            </div>
        @endif

        <div class="space-y-2 text-center text-sm text-gray-600">
            <p>{{ __('Before proceeding, please check your email for a verification link.') }}</p>
            <p>
                {{ __('If you did not receive the email') }},
                <a href="{{ route('verification.resend') }}" class="text-primary hover:text-primary-active underline">
                    {{ __('click here to request another') }} </a
                >.
            </p>
        </div>
    </div>
</x-layouts.auth>
