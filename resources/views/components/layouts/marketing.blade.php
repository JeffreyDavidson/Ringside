<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <meta name="description" content="{{ __('marketing.description') }}" />
    <meta name="theme-color" content="#101112" />
    <meta property="og:type" content="website" />
    <meta property="og:site_name" content="Ringside" />
    <meta property="og:title" content="{{ __('marketing.title') }}" />
    <meta property="og:description" content="{{ __('marketing.description') }}" />
    <meta property="og:url" content="{{ route('home') }}" />
    <meta property="og:image" content="{{ asset('images/marketing/arena.webp') }}" />
    <meta property="og:image:alt" content="{{ __('marketing.arena_alt') }}" />
    <meta name="twitter:card" content="summary_large_image" />
    <link rel="canonical" href="{{ route('home') }}" />
    <link
        rel="preload"
        href="{{ \Illuminate\Support\Facades\Vite::asset('resources/fonts/anton/anton-regular.ttf') }}"
        as="font"
        type="font/ttf"
        crossorigin
    />
    <title>{{ __('marketing.title') }}</title>
    @vite('resources/css/marketing.css')
</head>
<body>
    <a class="skip-link" href="#main">{{ __('marketing.skip') }}</a>
    {{ $slot }}
</body>
</html>
