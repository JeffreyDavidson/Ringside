<x-layouts.auth :title="__('auth-forms.reset_password')">
    <x-auth.form :title="__('auth-forms.reset_password')" :action="route('password.update')">
        <x-slot:intro>{{ __('auth-forms.reset_intro') }}</x-slot:intro>
        <input type="hidden" name="token" value="{{ $token }}" />
        <x-auth.field
            name="email"
            type="email"
            :value="$email ?? ''"
            :label="__('auth-forms.email')"
            autocomplete="username"
            required
        />
        <x-auth.field
            name="password"
            :hint="__('auth-forms.password_hint')"
            type="password"
            :label="__('auth-forms.password')"
            autocomplete="new-password"
            required
        />
        <x-auth.field
            name="password_confirmation"
            type="password"
            :label="__('auth-forms.confirm_password')"
            autocomplete="new-password"
            required
        />
        <x-button
            type="submit"
            variant="ringside"
            size="xl"
            class="w-full"
        >{{ __('auth-forms.reset_password') }}</x-button>
        <x-auth.link :href="route('login')" class="self-center">{{ __('auth-forms.back_to_login') }}</x-auth.link>
    </x-auth.form>
</x-layouts.auth>
