<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Laravel') }}</title>

		<link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Poppins:300,400,500,600,700" />
		<link href="{{ asset('assets/plugins/global/plugins.bundle.css') }}" rel="stylesheet" type="text/css" />
		<link href="{{ asset('assets/css/style.bundle.css') }}" rel="stylesheet" type="text/css" />
        @livewireStyles
    </head>
    <body id="kt_body" class="header-fixed header-tablet-and-mobile-fixed toolbar-enabled toolbar-fixed aside-enabled aside-fixed" style="--kt-toolbar-height:55px;--kt-toolbar-height-tablet-and-mobile:55px">
		<div class="d-flex flex-column flex-root">
			<div class="flex-row page d-flex flex-column-fluid">
                @include('partials.aside')

				<div class="wrapper d-flex flex-column flex-row-fluid" id="kt_wrapper">
                    @include('partials.header')

                    <div class="content d-flex flex-column flex-column-fluid" id="kt_content">
                        @include('partials.toolbar')

                        <div class="post d-flex flex-column-fluid" id="kt_post">
                            <div id="kt_content_container" class="container-xxl">
                                {{ $slot }}
                            </div>
                        </div>
                    </div>
                    <x-notification />

                    @include('partials.footer')
                </div>
            </div>
        </div>
		<script>var hostUrl = "assets/";</script>
		<script src="{{ asset('assets/js/app.js') }}"></script>
		<script src="{{ asset('assets/js/scripts.bundle.js') }}"></script>
		<script src="{{ asset('assets/plugins/global/plugins.bundle.js') }}"></script>
		<script src="{{ asset('assets/js/widgets.bundle.js') }}"></script>
		<script src="{{ asset('assets/js/custom/widgets.js') }}"></script>
        @livewireScripts
        <script src="{{ asset('assets/js/custom/tags.js') }}">
    </body>
</html>
