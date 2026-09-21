<x-layouts.auth :title="__('auth-forms.sign_in')">
    <x-auth.form :title="__('auth-forms.sign_in')" :action="route('login')">
        <x-slot:intro>
            {{ __('auth-forms.new_account') }}
            <x-auth.link :href="route('register')">{{ __('auth-forms.create_account') }}</x-auth.link>
        </x-slot:intro>
        <x-auth.field
            name="email"
            type="email"
            :label="__('auth-forms.email')"
            autocomplete="username"
            :placeholder="__('auth-forms.email_placeholder')"
            data-test="email"
        />
        <x-auth.field
            name="password"
            type="password"
            :label="__('auth-forms.password')"
            autocomplete="current-password"
            :placeholder="__('auth-forms.password_placeholder')"
            data-test="password"
        >
            <x-slot:action>
                <x-auth.link :href="route('password.request')" class="shrink-0 text-sm whitespace-nowrap">
                    {{ __('auth-forms.forgot_password') }}</x-auth.link>
            </x-slot:action>
        </x-auth.field>
        <label
            class="text-ringside-muted-bright flex min-h-11 w-fit cursor-pointer items-center gap-3 text-base"
            for="remember"
        >
            <input
                type="checkbox"
                name="remember"
                id="remember"
                value="1"
                data-test="remember"
                @checked(old('remember'))
                class="accent-ringside-red focus-visible:outline-ringside-white size-5 shrink-0 focus-visible:outline-2 focus-visible:outline-offset-4"
            />
            {{ __('auth-forms.remember') }}
        </label>
        <x-button
            type="submit"
            variant="ringside"
            size="xl"
            class="w-full"
            data-test="sign-in"
            :data-submitting-label="__('auth-forms.signing_in')"
        >{{ __('auth-forms.sign_in_action') }}</x-button>
    </x-auth.form>
</x-layouts.auth>
