<!DOCTYPE html>
<html class="h-full" lang="en" lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'Ringside') }}</title>

    <link rel="stylesheet"
        href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&amp;display=swap" />
    @vite('resources/js/app.js')
    @livewireStyles
</head>

<body
    class="antialiased flex h-full text-base text-gray-700 bg-[#fefefe]">
    <!-- Page -->
    <!-- Main -->
    <div class="flex grow">
        <!-- Sidebar -->
        <x-sidebar />
        <!-- End of Sidebar -->
        <!-- Wrapper -->
        <div class="lg:pt-[70px] lg:ps-[280px] flex grow flex-col">
            <!-- Header -->
            <x-layouts.partials.header />
            <!-- End of Header -->
            <!-- Content -->
            <main class="grow pt-5">
                {{ $slot }}
            </main>
            <!-- End of Content -->
            <!-- Footer -->
            <x-layouts.partials.footer />
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
