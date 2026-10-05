@props([
    'title',
    'description',
    'code' => null,
    'showDashboardLink' => true,
])

{{--
    Error pages can render before the session, the signed-in user or the promotion context is resolved, and a
    server error may come from the database itself. hasUser() only reports a user that was already loaded, so this
    page never queries for one.
--}}
@php
    $signedIn = auth()->hasUser();
@endphp

<x-layouts.auth :title="$title">
    <div class="flex flex-col gap-8">
        <header class="border-ringside-line border-b pb-6">
            @if ($code !== null)
                <p class="text-ringside-signal-soft text-sm font-bold tracking-wider uppercase">
                    {{ __('errors.code', ['code' => $code]) }}
                </p>
            @endif
            <h1 class="font-display mt-3 text-4xl leading-tight tracking-tight uppercase sm:text-5xl">{{ $title }}</h1>
            <p class="text-ringside-muted mt-4 text-base leading-relaxed">{{ $description }}</p>
        </header>

        {{ $slot }}

        <div class="flex flex-wrap items-center gap-x-6 gap-y-3">
            @if (! $signedIn)
                <x-button tag="a" variant="ringside" size="xl" :href="route('login')">
                    {{ __('errors.sign_in') }}
                </x-button>
            @else
                @if ($showDashboardLink)
                    <x-button tag="a" variant="ringside" size="xl" :href="route('dashboard')">
                        {{ __('errors.back_to_dashboard') }}
                    </x-button>
                @endif

                <form method="post" action="{{ route('logout') }}">
                    @csrf
                    <x-button type="submit" variant="secondary" size="xl"> {{ __('auth-forms.log_out') }} </x-button>
                </form>
            @endif
        </div>
    </div>
</x-layouts.auth>
