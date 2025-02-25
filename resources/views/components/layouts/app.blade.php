<!DOCTYPE html>
<html class="h-full" lang="en" lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'Ringside') }}</title>

    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&amp;display=swap" />
    @vite('resources/js/app.js')
    @livewireStyles

    @stack('scripts')
    @stack('styles')
</head>

<body class="antialiased flex h-full text-base text-gray-700 layout1 bg-[--page-bg]">
    <!-- Page -->
    <!-- Main -->
    <div class="flex grow">
        <!-- Sidebar -->
        <x-sidebar />
        <!-- End of Sidebar -->
        <!-- Wrapper -->
        <div class="pt-[--header-height] flex grow flex-col lg:pt-[--header-height] lg:ps-[--sidebar-width]">
            <!-- Header -->
            <x-layouts.partials.header />
            <!-- End of Header -->
            <!-- Content -->
            <main class="grow pt-5">
                <!-- Container -->
                <x-container-fixed>
                    <x-breadcrumbs class="flex lg:hidden" />
                </x-container-fixed>
                <!-- End of Container -->
                {{ $slot }}
            </main>
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
    @livewireScriptConfig
    @livewire('wire-elements-modal')
</body>

</html>
