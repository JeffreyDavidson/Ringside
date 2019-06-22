@extends('layouts.app')

@section('content-head')
<!-- begin:: Content Head -->
<div class="kt-subheader kt-grid__item" id="kt_subheader">
    <div class="kt-subheader__main">
        <h3 class="kt-subheader__title">{{ $venue->name }}</h3>
    </div>
    <div class="kt-subheader__toolbar">
        <a href="{{ route('venues.index') }}"
            class="btn btn-label-brand btn-bold">
            Back To Venues
        </a>
    </div>
</div>

<!-- end:: Content Head -->
@endsection

@section('content')
<!--Begin::App-->
<div class="kt-grid kt-grid--desktop kt-grid--ver kt-grid--ver-desktop kt-app">

    <!--Begin:: App Aside Mobile Toggle-->
    <button class="kt-app__aside-close" id="kt_user_profile_aside_close">
        <i class="la la-close"></i>
    </button>

    <!--End:: App Aside Mobile Toggle-->

    <!--Begin:: App Aside-->
    <div class="kt-grid__item kt-app__toggle kt-app__aside" id="kt_user_profile_aside">

        <!--begin:: Widgets/Applications/User/Profile1-->
        <div class="kt-portlet kt-portlet--height-fluid-">
            <div class="kt-portlet__head  kt-portlet__head--noborder">
                <div class="kt-portlet__head-label">
                    <h3 class="kt-portlet__head-title">
                    </h3>
                </div>
            </div>
            <div class="kt-portlet__body kt-portlet__body--fit-y">

                <!--begin::Widget -->
                <div class="kt-widget kt-widget--user-profile-1">
                    <div class="kt-widget__head">
                        <div class="kt-widget__content">
                            <div class="kt-widget__section">
                                <a href="#" class="kt-widget__username">
                                    {{ $venue->name }}
                                </a>
                            </div>
                        </div>
                    </div>
                    <div class="kt-widget__body">
                        <div class="kt-widget__content">
                            <div class="kt-widget__info">
                                <span class="kt-widget__label">Location:</span>
                                <span class="kt-widget__data">{!! $venue->fullAddress !!}</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!--end::Widget -->
            </div>
        </div>

        <!--end:: Widgets/Applications/User/Profile1-->
    </div>

    <!--End:: App Aside-->

    <!--Begin:: App Content-->
    <div class="kt-grid__item kt-grid__item--fluid kt-app__content">

    </div>

    <!--End:: App Content-->
</div>

<!--End::App-->



@endsection
