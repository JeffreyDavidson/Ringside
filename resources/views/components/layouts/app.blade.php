<!DOCTYPE html>
<html lang="en">

    <head>
        <meta charset="utf-8" />
        <meta name="description" content="Updates and statistics">
        <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">

        <!-- CSRF Token -->
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Ringside') }}</title>
        <style type="text/css"></style>

        <!--begin::Fonts -->
        <script src="https://ajax.googleapis.com/ajax/libs/webfont/1.6.16/webfont.js"></script>
        <script>
            WebFont.load({
                    google: {
                        "families": ["Poppins:300,400,500,600,700", "Roboto:300,400,500,600,700"]
                    },
                    active: function() {
                        sessionStorage.fonts = true;
                    }
                });
        </script>
        <link href="{{ asset('css/fonts.css') }}" rel="stylesheet" type="text/css" />
        <!--end::Fonts -->

        <link href="{{ asset('css/app.css') }}" rel="stylesheet" type="text/css" />
        <link href="{{ asset('css/vendors.css') }}" rel="stylesheet" type="text/css" />

        <link href="{{ asset('css/theme.css') }}" rel="stylesheet" type="text/css" />

        <livewire:styles>
    </head>

    <body
        class="kt-header--fixed kt-header-mobile--fixed kt-subheader--fixed kt-subheader--enabled kt-subheader--solid kt-aside--enabled kt-aside--fixed kt-page--loading">

        <!-- begin:: Page -->
        <div class="kt-grid kt-grid--hor kt-grid--root">
            <div class="kt-grid__item kt-grid__item--fluid kt-grid kt-grid--ver kt-page">

                <!-- begin:: Aside -->
                <button class="kt-aside-close " id="kt_aside_close_btn"><i class="la la-close"></i></button>
                <x-aside />

                <div class="kt-grid__item kt-grid__item--fluid kt-grid kt-grid--hor kt-wrapper" id="kt_wrapper">

                    <div id="kt_header" class="kt-header kt-grid__item  kt-header--fixed ">

                        <button class="kt-header-menu-wrapper-close"
                                id="kt_header_menu_mobile_close_btn"><i
                                class="la la-close">
                        </i></button>
                        <div class="kt-header-menu-wrapper" id="kt_header_menu_wrapper">
                            <div id="kt_header_menu" class="kt-header-menu kt-header-menu-mobile  kt-header-menu--layout-default ">
                            </div>
                        </div>

                        <x-header />
                        <x-header-topbar />

                    </div>

                    <div id="kt_content" class="kt-content kt-grid__item kt-grid__item--fluid kt-grid kt-grid--hor">
                        {{ $slot }}
                    </div>

                    <x-footer />
                </div>
            </div>
        </div>

        <!-- end:: Page -->

        <script>
            var KTAppOptions = {
                    "colors": {
                        "state": {
                            "brand": "#5d78ff",
                            "dark": "#282a3c",
                            "light": "#ffffff",
                            "primary": "#5867dd",
                            "success": "#34bfa3",
                            "info": "#36a3f7",
                            "warning": "#ffb822",
                            "danger": "#fd3995"
                        },
                        "base": {
                            "label": ["#c5cbe3", "#a1a8c3", "#3d4465", "#3e4466"],
                            "shape": ["#f0f3ff", "#d9dffa", "#afb4d4", "#646c9a"]
                        }
                    }
                };
        </script>
        <livewire:scripts>
        <script src="{{ asset('js/manifest.js') }}"></script>
        <script src="{{ asset('js/vendor.js') }}"></script>
        @stack('scripts-before')
        <script src="{{ asset('js/app.js') }}"></script>
        @stack('scripts-after')
    </body>
</html>
