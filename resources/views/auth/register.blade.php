<x-layouts.auth :title="__('auth-forms.sign_up')">
    <x-auth.form :title="__('auth-forms.sign_up')" :action="route('register')">
        <x-slot:intro>
            {{ __('auth-forms.existing_account') }}
            <x-auth.link :href="route('login')">{{ __('auth-forms.sign_in') }}</x-auth.link>
        </x-slot:intro>
        <div class="grid gap-6 sm:grid-cols-2">
            <x-auth.field name="first_name" :label="__('auth-forms.first_name')" autocomplete="given-name" required />
            <x-auth.field name="last_name" :label="__('auth-forms.last_name')" autocomplete="family-name" required />
        </div>
        <x-auth.field
            name="email"
            type="email"
            :label="__('auth-forms.email')"
            autocomplete="username"
            :placeholder="__('auth-forms.email_placeholder')"
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
        >{{ __('auth-forms.create_account') }}</x-button>
    </x-auth.form>
</x-layouts.auth>
