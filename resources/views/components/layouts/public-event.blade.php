@props(['page'])
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>{{ $page->title }}{{ $page->isResults ? ' — '.__('public-events.results') : '' }}</title>
    <meta name="description" content="{{ $page->scheduleChanged ? __('public-events.updating') : $page->title }}" />
    <meta property="og:title" content="{{ $page->title }}" />
    <meta
        property="og:description"
        content="{{ $page->scheduleChanged ? __('public-events.updating') : $page->title }}"
    />
    <meta property="og:type" content="website" />
    <link rel="canonical" href="{{ $page->cardUrl }}" />
    @if ($page->posterUrl && ! $page->isPreview)
        <meta property="og:image" content="{{ $page->posterUrl }}" />
        <meta property="og:image:alt" content="{{ $page->posterAlt }}" />
    @endif
    @if ($page->isPreview || $page->isResults)
        <meta name="robots" content="noindex, nofollow" />
    @endif
    <x-favicons />
    @vite('resources/css/app.css')
</head>
<body class="bg-surface text-foreground font-body text-base leading-relaxed">
    <a
        href="#event-details"
        class="bg-primary fixed top-4 left-4 z-50 -translate-y-24 px-5 py-3 font-bold focus:translate-y-0"
    >{{ __('public-events.skip') }}</a>
    {{ $slot }}
</body>
</html>
