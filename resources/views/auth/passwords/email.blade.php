<x-layouts.auth>
    <div class="w-full">
        <h1 class="mb-6 text-center text-lg font-semibold text-gray-900">{{ __('Reset Password') }}</h1>

        @if (session('status'))
            <div class="bg-success-light border-success text-success-700 mb-4 rounded border px-4 py-3">
                {{ session('status') }}
            </div>
        @endif

        <form method="POST" action="{{ route('password.email') }}" class="space-y-4">
            @csrf

            <div>
                <x-form.form-label name="email" label="{{ __('E-Mail Address') }}" />
                <input
                    type="email"
                    name="email"
                    id="email"
                    value="{{ old('email') }}"
                    required
                    autofocus
                    class="form-input-base form-input-default form-input-states @error('email') border-red-600 @enderror"
                />
                @error('email')
                    <div class="mt-1 text-sm text-red-600">{{ $message }}</div>
                @enderror
            </div>

            <div class="pt-2">
                <x-button variant="primary" type="submit" class="w-full">
                    {{ __('Send Password Reset Link') }}
                </x-button>
            </div>
        </form>
    </div>
</x-layouts.auth>
