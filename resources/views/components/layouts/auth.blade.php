<!DOCTYPE html>
<html class="h-full" lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <meta name="csrf-token" content="{{ csrf_token() }}" />

    <title>{{ config('app.name', 'Ringside') }}</title>

    <link
        rel="stylesheet"
        href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&amp;display=swap"
    />

    @vite('resources/js/auth.js')
</head>
<!-- end::Head -->

<body class="bg-background text-foreground flex h-full text-base antialiased">
    <!--begin::Root-->
    <div class="grid grow lg:grid-cols-2">
        <!-- Login Form Section -->
        <div class="order-2 flex items-center justify-center p-8 lg:order-1 lg:p-10">
            <x-card class="w-full max-w-[370px]"> {{ $slot }} </x-card>
        </div>

        <!-- Branded Background Section -->
        <div class="lg:border-border xxl:bg-center order-1 bg-[url('/images/bg-10.png')] bg-top bg-no-repeat lg:order-2 lg:m-5 lg:rounded-xl lg:border xl:bg-cover">
            <div class="flex flex-col gap-4 p-8 lg:p-16">
                <a href="{{ route('dashboard') }}">
                    <x-application-logo class="h-7 max-w-none" />
                </a>
                <div class="flex flex-col gap-3">
                    <h3 class="text-foreground text-2xl font-semibold">Secure Access Portal</h3>
                    <div class="text-muted-foreground text-base font-medium">
                        A robust authentication gateway ensuring<br />
                        secure <span class="text-foreground font-semibold">efficient user access</span> to the
                        Ringside<br />
                        Management interface.
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
