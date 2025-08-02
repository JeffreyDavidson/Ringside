<x-layouts.auth>
    <div class="w-full">
        <h1 class="text-lg font-semibold text-gray-900 mb-6 text-center">{{ __('Reset Password') }}</h1>
        
        <form method="POST" action="{{ route('password.update') }}" class="space-y-4">
            @csrf
            <input type="hidden" name="token" value="{{ $token }}">

            <div>
                <x-form.form-label name="email" label="{{ __('E-Mail Address') }}" />
                <input type="email" 
                       name="email" 
                       id="email"
                       value="{{ $email ?? old('email') }}" 
                       required 
                       autofocus
                       class="form-input-base form-input-default form-input-states @error('email') border-red-600 @enderror">
                @error('email')
                    <div class="text-red-600 text-sm mt-1">{{ $message }}</div>
                @enderror
            </div>

            <div>
                <x-form.form-label name="password" label="{{ __('Password') }}" />
                <input type="password" 
                       name="password" 
                       id="password"
                       required
                       class="form-input-base form-input-default form-input-states @error('password') border-red-600 @enderror">
                @error('password')
                    <div class="text-red-600 text-sm mt-1">{{ $message }}</div>
                @enderror
            </div>

            <div>
                <x-form.form-label name="password-confirm" label="{{ __('Confirm Password') }}" />
                <input type="password" 
                       name="password_confirmation" 
                       id="password-confirm"
                       required
                       class="form-input-base form-input-default form-input-states">
            </div>

            <div class="pt-2">
                <x-buttons.primary type="submit" class="w-full">
                    {{ __('Reset Password') }}
                </x-buttons.primary>
            </div>
        </form>
    </div>
</x-layouts.auth>
