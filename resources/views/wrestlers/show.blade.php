@extends('layouts.app')

@section('content-head')
<!-- begin:: Content Head -->
<div class="kt-subheader kt-grid__item" id="kt_subheader">
    <div class="kt-subheader__main">
        <h3 class="kt-subheader__title">{{ $wrestler->name }}</h3>
    </div>
</div>

<!-- end:: Content Head -->
@endsection

@section('content')
<!--begin:: Widgets/Applications/User/Profile1-->
<div class="row">
    <div class="col-lg-3">
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
                        <div class="kt-widget__media">
                            <img src="https://via.placeholder.com/100" alt="image">
                        </div>
                        <div class="kt-widget__content">
                            <div class="kt-widget__section">
                                <span class="kt-widget__username"">{{ $wrestler->name }}</span>
                            </div>
                        </div>
                    </div>
                    <div class="kt-widget__body">
                        <div class="kt-widget__content">
                            <div class="kt-widget__info">
                                <span class="kt-widget__label">Height:</span>
                                <span class="kt-widget__data">{{ $wrestler->formatted_height }}</span>
                            </div>
                            <div class="kt-widget__info">
                                <span class="kt-widget__label">Weight:</span>
                                <span class="kt-widget__data">{{ $wrestler->weight }} lbs.</span>
                            </div>
                            <div class="kt-widget__info">
                                <span class="kt-widget__label">Hometown:</span>
                                <span class="kt-widget__data">{{ $wrestler->hometown }}</span>
                            </div>
                            <div class="kt-widget__info">
                                <span class="kt-widget__label">Signature Move:</span>
                                <span class="kt-widget__data">{{ $wrestler->signature_move }}</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!--end::Widget -->
                </div>
            </div>

            <!--end:: Widgets/Applications/User/Profile1-->
        </div>
    </div>
</div>

<!--End::App-->
@endsection
