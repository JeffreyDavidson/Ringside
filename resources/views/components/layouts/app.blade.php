<!DOCTYPE html>
<html class="h-full" lang="en" lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <meta name="csrf-token" content="{{ csrf_token() }}" />

    <title>{{ config('app.name', 'Ringside') }}</title>

    <link
        rel="stylesheet"
        href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&amp;display=swap"
    />
    @vite('resources/js/app.js')
    @livewireStyles

    @stack('scripts')
    @stack('styles')
</head>

<body class="layout1 flex h-full bg-[--page-bg] text-base text-gray-700 antialiased">
    <!-- Page -->
    <!-- Main -->
    <div class="flex grow">
        <!-- Sidebar -->
        <x-sidebar />
        <!-- End of Sidebar -->
        <!-- Wrapper -->
        <div
            class="flex grow flex-col pt-[--header-height] transition-all duration-300 lg:pt-[--header-height]"
            x-data
            :class="$store.sidebar && $store.sidebar.expanded
                ? 'lg:ps-[--sidebar-default-width]'
                : 'lg:ps-[--sidebar-collapsed-width]'"
        >
            <!-- Header -->
            <x-layouts.partials.header />
            <!-- End of Header -->
            <x-flash-messages />
            <!-- Content -->
            <main class="grow pt-5">{{ $slot }}</main>
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
