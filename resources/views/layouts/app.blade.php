<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Laravel') }}</title>

        <!--begin::Fonts-->
		<link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Poppins:300,400,500,600,700" />
		<!--end::Fonts-->
		<!--begin::Page Vendor Stylesheets(used by this page)-->
		<link href="{{ asset('assets/plugins/custom/fullcalendar/fullcalendar.bundle.css') }}" rel="stylesheet" type="text/css" />
		<link href="{{ asset('assets/plugins/custom/datatables/datatables.bundle.css') }}" rel="stylesheet" type="text/css" />
        <!--end::Page Vendor Stylesheets-->
        <!--begin::Global Stylesheets Bundle(used by all pages)-->
        <link rel="stylesheet" href="{{ asset('assets/plugins/global/plugins.bundle.css') }}">
        <link rel="stylesheet" href="{{ asset('assets/css/style.bundle.css') }}">
        <!--end::Global Stylesheets Bundle-->
    </head>
    <body id="kt_body" class="header-fixed header-tablet-and-mobile-fixed toolbar-enabled toolbar-fixed aside-enabled aside-fixed" style="--kt-toolbar-height:55px;--kt-toolbar-height-tablet-and-mobile:55px">
        <!--begin::Main-->
		<!--begin::Root-->
		<div class="d-flex flex-column flex-root">
			<!--begin::Page-->
			<div class="flex-row page d-flex flex-column-fluid">

				<!--begin::Aside-->
                @include('partials.aside')
                <!--end::Aside-->

                <!--begin::Wrapper-->
				<div class="wrapper d-flex flex-column flex-row-fluid" id="kt_wrapper">
					<!--begin::Header-->
                    @include('partials.header2')
                    <!--end::Header>

                    <!--begin::Content-->
					<div class="content d-flex flex-column flex-column-fluid" id="kt_content">

                        <!--begin::Toolbar-->
						@include('partials.toolbar')
                        <!--end::Toolbar>

                        <!--begin::Post-->
                        <div class="post d-flex flex-column-fluid" id="kt_post">
                            <!--begin::Container-->
                            <div id="kt_content_container" class="container-xxl">
                                {{ $slot }}
                            <!--end::Container-->
                        </div>
                        <!--end::Post-->
                    </div>
                    <!--end::Content>

                    <!--begin::Footer-->
                    @include('partials.footer')
                    <!--end::Footer-->
                </div>
                <!--end::Wrapper-->
            </div>
            <!--end::Page-->
        </div>
		<!--end::Root-->

        <!--begin::Javascript-->
		<script>var hostUrl = "assets/";</script>
		<!--begin::Global Javascript Bundle(used by all pages)-->
		<script src="{{ asset('assets/plugins/global/plugins.bundle.js') }}"></script>
		<script src="{{ asset('assets/js/scripts.bundle.js') }}"></script>
		<!--end::Global Javascript Bundle-->
		<!--begin::Page Vendors Javascript(used by this page)-->
		<script src="{{ asset('assets/plugins/custom/fullcalendar/fullcalendar.bundle.js') }}"></script>
		<script src="{{ asset('assets/plugins/custom/datatables/datatables.bundle.js') }}"></script>
		<!--end::Page Vendors Javascript-->
		<!--begin::Page Custom Javascript(used by this page)-->
		<script src="{{ asset('assets/js/widgets.bundle.js') }}"></script>
		<script src="{{ asset('assets/js/custom/widgets.js') }}"></script>
		<script src="{{ asset('assets/js/custom/apps/chat/chat.js') }}"></script>
		<script src="{{ asset('assets/js/custom/utilities/modals/upgrade-plan.js') }}"></script>
		<script src="{{ asset('assets/js/custom/utilities/modals/create-app.js') }}"></script>
		<script src="{{ asset('assets/js/custom/utilities/modals/users-search.js') }}"></script>
		<!--end::Page Custom Javascript-->
		<!--end::Javascript-->

    </body>
</html>
