@props(['title' => 'Ringside'])

<!DOCTYPE html>
<html class="h-full" lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <meta name="csrf-token" content="{{ csrf_token() }}" />
    <title>{{ $title }} · Ringside</title>
    @vite('resources/js/auth.js')
</head>
<body class="bg-ringside-surface-deep font-body text-ringside-ink selection:bg-ringside-red selection:text-ringside-white m-0 min-h-dvh antialiased">
    <div class="grid min-h-dvh lg:grid-cols-2">
        <aside
            class="bg-ringside-surface relative hidden flex-col justify-between gap-16 overflow-hidden p-12 lg:flex xl:p-16"
            aria-label="Ringside"
        >
            <a
                class="font-display focus-visible:outline-ringside-white w-fit text-5xl leading-none tracking-tight focus-visible:outline-2 focus-visible:outline-offset-6"
                href="{{ route('home') }}"
                aria-label="{{ __('auth-forms.home') }}"
            >RING<span class="text-ringside-signal">SIDE</span></a>
            <div class="max-w-lg">
                <p class="font-display text-6xl leading-tight tracking-tight uppercase xl:text-7xl">
                    {{ __('auth-forms.brand_title') }}<br /><span
                        class="text-ringside-signal"
                        >{{ __('auth-forms.brand_emphasis') }}</span>
                </p>
                <p class="text-ringside-muted mt-8 max-w-sm text-lg leading-relaxed">
                    {{ __('auth-forms.brand_description') }}
                </p>
            </div>
            <div class="text-ringside-muted-soft flex flex-wrap items-center gap-3 text-xs font-bold tracking-wider uppercase">
                <span>{{ __('auth-forms.roster') }}</span>
                <span class="bg-ringside-line-bright h-px w-6" aria-hidden="true"></span>
                <span>{{ __('auth-forms.events') }}</span>
                <span class="bg-ringside-line-bright h-px w-6" aria-hidden="true"></span>
                <span>{{ __('auth-forms.championships') }}</span>
            </div>
        </aside>
        <main class="flex min-h-dvh items-center justify-center px-5 py-10 sm:px-10 lg:p-12 xl:p-16">
            <div class="w-full max-w-md">
                <a
                    class="font-display focus-visible:outline-ringside-white mb-10 inline-block text-4xl leading-none tracking-tight focus-visible:outline-2 focus-visible:outline-offset-6 lg:hidden"
                    href="{{ route('home') }}"
                    aria-label="{{ __('auth-forms.home') }}"
                >RING<span class="text-ringside-signal">SIDE</span></a>
                {{ $slot }}
            </div>
        </main>
    </div>
</body>
</html>
