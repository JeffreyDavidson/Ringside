<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" data-bs-theme="light">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Laravel') }}</title>

        <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Inter:300,400,500,600,700">
        <link href="{{ asset('assets/plugins/global/plugins.bundle.css') }}" rel="stylesheet" type="text/css"/>
        <link href="{{ asset('assets/css/style.bundle.css') }}" rel="stylesheet" type="text/css"/>
        @vite('resources/css/app.css')
        @livewireStyles
    </head>
    <body id="kt_app_body" data-kt-app-layout="dark-sidebar" data-kt-app-header-fixed="true"
          data-kt-app-sidebar-enabled="true" data-kt-app-sidebar-fixed="true" data-kt-app-sidebar-hoverable="true"
          data-kt-app-sidebar-push-header="true" data-kt-app-sidebar-push-toolbar="true"
          data-kt-app-sidebar-push-footer="true" data-kt-app-toolbar-enabled="true" class="app-default"
          data-new-gr-c-s-check-loaded="8.909.0" data-gr-ext-installed="" listener="true">
        <!--begin::Theme mode setup on page load-->
        <script>var defaultThemeMode = "light";
            var themeMode;
            if (document.documentElement) {
                if (document.documentElement.hasAttribute("data-bs-theme-mode")) {
                    themeMode = document.documentElement.getAttribute("data-bs-theme-mode");
                } else {
                    if (localStorage.getItem("data-bs-theme") !== null) {
                        themeMode = localStorage.getItem("data-bs-theme");
                    } else {
                        themeMode = defaultThemeMode;
                    }
                }
                if (themeMode === "system") {
                    themeMode = window.matchMedia("(prefers-color-scheme: dark)").matches ? "dark" : "light";
                }
                document.documentElement.setAttribute("data-bs-theme", themeMode);
            }
        </script>
        <!--end::Theme mode setup on page load-->
        <div id="kt_app_root" class="d-flex flex-column flex-root app-root">
            <div id="kt_app_page" class="app-page flex-column flex-column-fluid">
                @include('partials.header')
                <div id="kt_app_wrapper" class="app-wrapper flex-column flex-row-fluid">
                    <x-layouts.partials.aside/>
                    <div id="kt_app_main" class="app-main flex-column flex-row-fluid">
                        <div class="d-flex flex-column flex-column-fluid">
                            {{ $toolbar }}
                            <div id="kt_app_content" class="app-content flex-column-fluid">
                                <div id="kt_app_content_container" class="app-container container-fluid">
                                    {{ $slot }}
                                </div>
                            </div>
                        </div>
                        <x-layouts.partials.footer/>
                    </div>
                </div>
            </div>
        </div>

        <script>var hostUrl = "assets/";</script>
        <script src="{{ asset('assets/plugins/global/plugins.bundle.js') }}"></script>
        <script src="{{ asset('assets/js/scripts.bundle.js') }}"></script>
        @vite('resources/js/app.js')
        @livewireScripts
    </body>
</html>
