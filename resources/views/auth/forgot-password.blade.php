@php
    $recoveryEmail = session('recovery_email');
    $linkRequested = is_string($recoveryEmail) && $recoveryEmail !== '';
    $resendAt = session('recovery_resend_at');
    $resendSeconds = is_int($resendAt) ? max(0, $resendAt - now()->timestamp) : 0;
@endphp

<x-layouts.auth :title="$linkRequested ? __('auth-forms.check_email') : __('auth-forms.forgot_password')">
    @if ($linkRequested)
        <div class="flex flex-col gap-6">
            <header class="border-ringside-line border-b pb-6">
                <h1 class="font-display text-4xl leading-tight tracking-tight uppercase sm:text-5xl">
                    {{ __('auth-forms.check_email') }}
                </h1>
                <p role="status" class="text-ringside-muted mt-4 text-base leading-relaxed">
                    {{ $errors->any() ? __('auth-forms.reset_requested_for') : __('auth-forms.reset_sent_to') }}
                    <strong class="text-ringside-ink block wrap-anywhere">{{ $recoveryEmail }}</strong>
                </p>
            </header>
            <p class="text-ringside-muted text-base leading-relaxed">{{ __('auth-forms.check_spam') }}</p>
            @error('email')
                <p
                    role="alert"
                    tabindex="-1"
                    data-auth-error
                    class="text-ringside-signal-soft focus-visible:outline-ringside-white focus-visible:outline-2 focus-visible:outline-offset-4"
                >
                    {{ $message }}
                </p>
            @enderror
            <form method="post" action="{{ route('password.email') }}">
                @csrf
                <input type="hidden" name="email" value="{{ $recoveryEmail }}" />
                <x-button
                    type="submit"
                    variant="ringside"
                    size="xl"
                    class="w-full"
                    :data-submitting-label="__('auth-forms.sending_link')"
                    :data-resend-seconds="$resendSeconds"
                    aria-describedby="resend-status"
                >{{ __('auth-forms.resend_link') }}</x-button>
                <p
                    id="resend-status"
                    class="text-ringside-muted mt-3 text-sm leading-relaxed"
                    data-wait-label="{{ __('auth-forms.resend_wait') }}"
                    data-ready-label="{{ __('auth-forms.resend_ready') }}"
                >
                    {{ $resendSeconds > 0 ? __('auth-forms.resend_wait', ['seconds' => $resendSeconds]) : __('auth-forms.resend_ready') }}
                </p>
            </form>
            <div class="flex flex-wrap justify-between gap-x-6 gap-y-2">
                <x-auth.link :href="route('password.request')">{{ __('auth-forms.change_email') }}</x-auth.link>
                <x-auth.link :href="route('login')">{{ __('auth-forms.back_to_login') }}</x-auth.link>
            </div>
        </div>
    @else
        <x-auth.form :title="__('auth-forms.forgot_password')" :action="route('password.email')">
            <x-slot:intro>{{ __('auth-forms.recovery_intro') }}</x-slot:intro>
            <x-auth.field
                name="email"
                type="email"
                :label="__('auth-forms.email')"
                autocomplete="username"
                :placeholder="__('auth-forms.email_placeholder')"
                required
            />
            <x-button
                type="submit"
                variant="ringside"
                size="xl"
                class="w-full"
                :data-submitting-label="__('auth-forms.sending_link')"
            >{{ __('auth-forms.send_reset_link') }}</x-button>
            <x-auth.link :href="route('login')" class="self-center">{{ __('auth-forms.back_to_login') }}</x-auth.link>
        </x-auth.form>
    @endif
</x-layouts.auth>
