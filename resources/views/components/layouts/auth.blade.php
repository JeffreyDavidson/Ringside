<!DOCTYPE html>
<html class="h-full" lang="{{ str_replace('_', '-', app()->getLocale()) }}">
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
<!-- end::Head -->

<body class="antialiased flex h-full text-base text-gray-700">
    <!--begin::Root-->
    <div class="flex items-center justify-center grow bg-center bg-no-repeat bg-[url('/images/bg-10.png')]">
        <x-card class="max-w-[370px] w-full">
            <x-card.body>
                {{ $slot }}
            </x-card.body>
        </x-card>
    </div>
</body>
</html>
