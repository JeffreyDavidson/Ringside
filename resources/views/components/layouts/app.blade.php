<!DOCTYPE html>
<html class="h-full" lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <meta name="csrf-token" content="{{ csrf_token() }}" />

    <title>{{ \Illuminate\Support\Facades\Config::string('app.name', 'Ringside') }}</title>

    <link
        rel="stylesheet"
        href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&amp;display=swap"
    />
    @vite('resources/js/app.js')
    @livewireStyles

    @stack('scripts')
    @stack('styles')
</head>

<body class="layout1 bg-ringside-surface text-ringside-ink min-h-dvh antialiased">
    <!-- Page -->
    <!-- Main -->
    <div class="flex h-dvh min-h-dvh grow overflow-hidden">
        <!-- Sidebar -->
        <x-sidebar />
        <!-- End of Sidebar -->
        <!-- Wrapper -->
        <div
            class="flex h-dvh min-h-dvh min-w-0 grow flex-col overflow-hidden pt-[var(--header-height)] transition-[padding] duration-300 ease-out lg:pt-[var(--header-height)]"
            x-data
            :class="$store.sidebar && $store.sidebar.expanded
                ? 'lg:ps-[var(--sidebar-default-width)]'
                : 'lg:ps-[var(--sidebar-collapsed-width)]'"
        >
            <!-- Header -->
            <x-layouts.partials.header />
            <!-- End of Header -->
            <x-flash-messages />
            <!-- Content -->
            <main class="min-h-0 min-w-0 grow overflow-y-auto p-4 lg:p-7">{{ $slot }}</main>
            <!-- End of Content -->
            <!-- Footer -->
            @persist('page-footer')
                <x-layouts.partials.footer />
            @endpersist
            <!-- End of Footer -->
        </div>
        <!-- End of Wrapper -->
    </div>
    <!-- End of Main -->
    <!-- End of Page -->
    @livewire('wire-elements-modal')
    @livewireScriptConfig
</body>
</html>
