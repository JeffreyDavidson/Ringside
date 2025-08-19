<!DOCTYPE html>
<html class="h-full" lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'Ringside') }}</title>

    <link rel="stylesheet"
        href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&amp;display=swap" />

    @vite('resources/js/auth.js')
</head>
<!-- end::Head -->

<body class="antialiased flex h-full text-base text-gray-700">
    <!--begin::Root-->
    <div class="grid lg:grid-cols-2 grow">
        <!-- Login Form Section -->
        <div class="flex justify-center items-center p-8 lg:p-10 order-2 lg:order-1">
            <x-card class="max-w-[370px] w-full">
                <x-card.body>
                    {{ $slot }}
                </x-card.body>
            </x-card>
        </div>
        
        <!-- Branded Background Section -->
        <div class="lg:rounded-xl lg:border lg:border-gray-200 lg:m-5 order-1 lg:order-2 bg-top xxl:bg-center xl:bg-cover bg-no-repeat bg-[url('/images/bg-10.png')]">
            <div class="flex flex-col p-8 lg:p-16 gap-4">
                <a href="{{ route('dashboard') }}">
                    <x-application-logo class="h-7 max-w-none" />
                </a>
                <div class="flex flex-col gap-3">
                    <h3 class="text-2xl font-semibold text-gray-900">
                        Secure Access Portal
                    </h3>
                    <div class="text-base font-medium text-gray-600">
                        A robust authentication gateway ensuring<br>
                        secure <span class="text-gray-900 font-semibold">efficient user access</span> to the Ringside<br>
                        Management interface.
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
