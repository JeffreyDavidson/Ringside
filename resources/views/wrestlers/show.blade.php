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
<!--Begin::App-->
<div class="kt-grid kt-grid--desktop kt-grid--ver kt-grid--ver-desktop kt-app" id="kt_app">
    <div class="kt-grid__item kt-app__toggle kt-app__aside" id="kt_app_aside">

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
    <div class="kt-grid__item kt-grid__item--fluid kt-app__content">
        <div class="row">
            <div class="col-xl-6">

                <!--begin:: Widgets/Order Statistics-->
                <div class="kt-portlet kt-portlet--height-fluid">
                    <div class="kt-portlet__head">
                        <div class="kt-portlet__head-label">
                            <h3 class="kt-portlet__head-title">
                                Order Statistics
                            </h3>
                        </div>
                        <div class="kt-portlet__head-toolbar">
                            <a href="#" class="btn btn-label-brand btn-bold btn-sm dropdown-toggle" data-toggle="dropdown">
                                Export
                            </a>
                            <div class="dropdown-menu dropdown-menu-fit dropdown-menu-right">

                                <!--begin::Nav-->
                                <ul class="kt-nav">
                                    <li class="kt-nav__head">
                                        Export Options
                                        <i class="flaticon2-information" data-toggle="kt-tooltip" data-placement="right" title="Click to learn more..."></i>
                                    </li>
                                    <li class="kt-nav__separator"></li>
                                    <li class="kt-nav__item">
                                        <a href="#" class="kt-nav__link">
                                            <i class="kt-nav__link-icon flaticon2-drop"></i>
                                            <span class="kt-nav__link-text">Activity</span>
                                        </a>
                                    </li>
                                    <li class="kt-nav__item">
                                        <a href="#" class="kt-nav__link">
                                            <i class="kt-nav__link-icon flaticon2-calendar-8"></i>
                                            <span class="kt-nav__link-text">FAQ</span>
                                        </a>
                                    </li>
                                    <li class="kt-nav__item">
                                        <a href="#" class="kt-nav__link">
                                            <i class="kt-nav__link-icon flaticon2-link"></i>
                                            <span class="kt-nav__link-text">Settings</span>
                                        </a>
                                    </li>
                                    <li class="kt-nav__item">
                                        <a href="#" class="kt-nav__link">
                                            <i class="kt-nav__link-icon flaticon2-new-email"></i>
                                            <span class="kt-nav__link-text">Support</span>
                                            <span class="kt-nav__link-badge">
                                                <span class="kt-badge kt-badge--success">5</span>
                                            </span>
                                        </a>
                                    </li>
                                    <li class="kt-nav__separator"></li>
                                    <li class="kt-nav__foot">
                                        <a class="btn btn-label-danger btn-bold btn-sm" href="#">Upgrade plan</a>
                                        <a class="btn btn-clean btn-bold btn-sm" href="#" data-toggle="kt-tooltip" data-placement="right" title="Click to learn more...">Learn more</a>
                                    </li>
                                </ul>

                                <!--end::Nav-->
                            </div>
                        </div>
                    </div>
                    <div class="kt-portlet__body kt-portlet__body--fluid">
                        <div class="kt-widget12">
                            <div class="kt-widget12__content">
                                <div class="kt-widget12__item">
                                    <div class="kt-widget12__info">
                                        <span class="kt-widget12__desc">Annual Taxes EMS</span>
                                        <span class="kt-widget12__value">$400,000</span>
                                    </div>
                                    <div class="kt-widget12__info">
                                        <span class="kt-widget12__desc">Finance Review Date</span>
                                        <span class="kt-widget12__value">July 24,2019</span>
                                    </div>
                                </div>
                                <div class="kt-widget12__item">
                                    <div class="kt-widget12__info">
                                        <span class="kt-widget12__desc">Avarage Revenue</span>
                                        <span class="kt-widget12__value">$60M</span>
                                    </div>
                                    <div class="kt-widget12__info">
                                        <span class="kt-widget12__desc">Revenue Margin</span>
                                        <div class="kt-widget12__progress">
                                            <div class="progress kt-progress--sm">
                                                <div class="progress-bar kt-bg-brand" role="progressbar" style="width: 40%;" aria-valuenow="100" aria-valuemin="0" aria-valuemax="100"></div>
                                            </div>
                                            <span class="kt-widget12__stat">
                                                40%
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="kt-widget12__chart" style="height:250px;">
                                <canvas id="kt_chart_order_statistics"></canvas>
                            </div>
                        </div>
                    </div>
                </div>

                <!--end:: Widgets/Order Statistics-->
            </div>
            <div class="col-xl-6">

                <!--begin:: Widgets/Tasks -->
                <div class="kt-portlet kt-portlet--tabs kt-portlet--height-fluid">
                    <div class="kt-portlet__head">
                        <div class="kt-portlet__head-label">
                            <h3 class="kt-portlet__head-title">
                                Tasks
                            </h3>
                        </div>
                        <div class="kt-portlet__head-toolbar">
                            <ul class="nav nav-tabs nav-tabs-line nav-tabs-bold nav-tabs-line-brand" role="tablist">
                                <li class="nav-item">
                                    <a class="nav-link active" data-toggle="tab" href="#kt_widget2_tab1_content" role="tab">
                                        Today
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link" data-toggle="tab" href="#kt_widget2_tab2_content" role="tab">
                                        Week
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link" data-toggle="tab" href="#kt_widget2_tab3_content" role="tab">
                                        Month
                                    </a>
                                </li>
                            </ul>
                        </div>
                    </div>
                    <div class="kt-portlet__body">
                        <div class="tab-content">
                            <div class="tab-pane active" id="kt_widget2_tab1_content">
                                <div class="kt-widget2">
                                    <div class="kt-widget2__item kt-widget2__item--primary">
                                        <div class="kt-widget2__checkbox">
                                            <label class="kt-checkbox kt-checkbox--solid kt-checkbox--single">
                                                <input type="checkbox">
                                                <span></span>
                                            </label>
                                        </div>
                                        <div class="kt-widget2__info">
                                            <a href="#" class="kt-widget2__title">
                                                Make Metronic Great Again.Lorem Ipsum Amet
                                            </a>
                                            <a href="#" class="kt-widget2__username">
                                                By Bob
                                            </a>
                                        </div>
                                        <div class="kt-widget2__actions">
                                            <a href="#" class="btn btn-clean btn-sm btn-icon btn-icon-md" data-toggle="dropdown">
                                                <i class="flaticon-more-1"></i>
                                            </a>
                                            <div class="dropdown-menu dropdown-menu-fit dropdown-menu-right">
                                                <ul class="kt-nav">
                                                    <li class="kt-nav__item">
                                                        <a href="#" class="kt-nav__link">
                                                            <i class="kt-nav__link-icon flaticon2-line-chart"></i>
                                                            <span class="kt-nav__link-text">Reports</span>
                                                        </a>
                                                    </li>
                                                    <li class="kt-nav__item">
                                                        <a href="#" class="kt-nav__link">
                                                            <i class="kt-nav__link-icon flaticon2-send"></i>
                                                            <span class="kt-nav__link-text">Messages</span>
                                                        </a>
                                                    </li>
                                                    <li class="kt-nav__item">
                                                        <a href="#" class="kt-nav__link">
                                                            <i class="kt-nav__link-icon flaticon2-pie-chart-1"></i>
                                                            <span class="kt-nav__link-text">Charts</span>
                                                        </a>
                                                    </li>
                                                    <li class="kt-nav__item">
                                                        <a href="#" class="kt-nav__link">
                                                            <i class="kt-nav__link-icon flaticon2-avatar"></i>
                                                            <span class="kt-nav__link-text">Members</span>
                                                        </a>
                                                    </li>
                                                    <li class="kt-nav__item">
                                                        <a href="#" class="kt-nav__link">
                                                            <i class="kt-nav__link-icon flaticon2-settings"></i>
                                                            <span class="kt-nav__link-text">Settings</span>
                                                        </a>
                                                    </li>
                                                </ul>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="kt-widget2__item kt-widget2__item--warning">
                                        <div class="kt-widget2__checkbox">
                                            <label class="kt-checkbox kt-checkbox--solid kt-checkbox--single">
                                                <input type="checkbox">
                                                <span></span>
                                            </label>
                                        </div>
                                        <div class="kt-widget2__info">
                                            <a href="#" class="kt-widget2__title">
                                                Prepare Docs For Metting On Monday
                                            </a>
                                            <a href="#" class="kt-widget2__username">
                                                By Sean
                                            </a>
                                        </div>
                                        <div class="kt-widget2__actions">
                                            <a href="#" class="btn btn-clean btn-sm btn-icon btn-icon-md" data-toggle="dropdown">
                                                <i class="flaticon-more-1"></i>
                                            </a>
                                            <div class="dropdown-menu dropdown-menu-fit dropdown-menu-right">
                                                <ul class="kt-nav">
                                                    <li class="kt-nav__item">
                                                        <a href="#" class="kt-nav__link">
                                                            <i class="kt-nav__link-icon flaticon2-line-chart"></i>
                                                            <span class="kt-nav__link-text">Reports</span>
                                                        </a>
                                                    </li>
                                                    <li class="kt-nav__item">
                                                        <a href="#" class="kt-nav__link">
                                                            <i class="kt-nav__link-icon flaticon2-send"></i>
                                                            <span class="kt-nav__link-text">Messages</span>
                                                        </a>
                                                    </li>
                                                    <li class="kt-nav__item">
                                                        <a href="#" class="kt-nav__link">
                                                            <i class="kt-nav__link-icon flaticon2-pie-chart-1"></i>
                                                            <span class="kt-nav__link-text">Charts</span>
                                                        </a>
                                                    </li>
                                                    <li class="kt-nav__item">
                                                        <a href="#" class="kt-nav__link">
                                                            <i class="kt-nav__link-icon flaticon2-avatar"></i>
                                                            <span class="kt-nav__link-text">Members</span>
                                                        </a>
                                                    </li>
                                                    <li class="kt-nav__item">
                                                        <a href="#" class="kt-nav__link">
                                                            <i class="kt-nav__link-icon flaticon2-settings"></i>
                                                            <span class="kt-nav__link-text">Settings</span>
                                                        </a>
                                                    </li>
                                                </ul>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="kt-widget2__item kt-widget2__item--brand">
                                        <div class="kt-widget2__checkbox">
                                            <label class="kt-checkbox kt-checkbox--solid kt-checkbox--single">
                                                <input type="checkbox">
                                                <span></span>
                                            </label>
                                        </div>
                                        <div class="kt-widget2__info">
                                            <a href="#" class="kt-widget2__title">
                                                Make Widgets Great Again.Estudiat Communy Elit
                                            </a>
                                            <a href="#" class="kt-widget2__username">
                                                By Aziko
                                            </a>
                                        </div>
                                        <div class="kt-widget2__actions">
                                            <a href="#" class="btn btn-clean btn-sm btn-icon btn-icon-md" data-toggle="dropdown">
                                                <i class="flaticon-more-1"></i>
                                            </a>
                                            <div class="dropdown-menu dropdown-menu-fit dropdown-menu-right">
                                                <ul class="kt-nav">
                                                    <li class="kt-nav__item">
                                                        <a href="#" class="kt-nav__link">
                                                            <i class="kt-nav__link-icon flaticon2-line-chart"></i>
                                                            <span class="kt-nav__link-text">Reports</span>
                                                        </a>
                                                    </li>
                                                    <li class="kt-nav__item">
                                                        <a href="#" class="kt-nav__link">
                                                            <i class="kt-nav__link-icon flaticon2-send"></i>
                                                            <span class="kt-nav__link-text">Messages</span>
                                                        </a>
                                                    </li>
                                                    <li class="kt-nav__item">
                                                        <a href="#" class="kt-nav__link">
                                                            <i class="kt-nav__link-icon flaticon2-pie-chart-1"></i>
                                                            <span class="kt-nav__link-text">Charts</span>
                                                        </a>
                                                    </li>
                                                    <li class="kt-nav__item">
                                                        <a href="#" class="kt-nav__link">
                                                            <i class="kt-nav__link-icon flaticon2-avatar"></i>
                                                            <span class="kt-nav__link-text">Members</span>
                                                        </a>
                                                    </li>
                                                    <li class="kt-nav__item">
                                                        <a href="#" class="kt-nav__link">
                                                            <i class="kt-nav__link-icon flaticon2-settings"></i>
                                                            <span class="kt-nav__link-text">Settings</span>
                                                        </a>
                                                    </li>
                                                </ul>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="kt-widget2__item kt-widget2__item--success">
                                        <div class="kt-widget2__checkbox">
                                            <label class="kt-checkbox kt-checkbox--solid kt-checkbox--single">
                                                <input type="checkbox">
                                                <span></span>
                                            </label>
                                        </div>
                                        <div class="kt-widget2__info">
                                            <a href="#" class="kt-widget2__title">
                                                Make Metronic Great Again. Lorem Ipsum
                                            </a>
                                            <a class="kt-widget2__username">
                                                By James
                                            </a>
                                        </div>
                                        <div class="kt-widget2__actions">
                                            <a href="#" class="btn btn-clean btn-sm btn-icon btn-icon-md" data-toggle="dropdown">
                                                <i class="flaticon-more-1"></i>
                                            </a>
                                            <div class="dropdown-menu dropdown-menu-fit dropdown-menu-right">
                                                <ul class="kt-nav">
                                                    <li class="kt-nav__item">
                                                        <a href="#" class="kt-nav__link">
                                                            <i class="kt-nav__link-icon flaticon2-line-chart"></i>
                                                            <span class="kt-nav__link-text">Reports</span>
                                                        </a>
                                                    </li>
                                                    <li class="kt-nav__item">
                                                        <a href="#" class="kt-nav__link">
                                                            <i class="kt-nav__link-icon flaticon2-send"></i>
                                                            <span class="kt-nav__link-text">Messages</span>
                                                        </a>
                                                    </li>
                                                    <li class="kt-nav__item">
                                                        <a href="#" class="kt-nav__link">
                                                            <i class="kt-nav__link-icon flaticon2-pie-chart-1"></i>
                                                            <span class="kt-nav__link-text">Charts</span>
                                                        </a>
                                                    </li>
                                                    <li class="kt-nav__item">
                                                        <a href="#" class="kt-nav__link">
                                                            <i class="kt-nav__link-icon flaticon2-avatar"></i>
                                                            <span class="kt-nav__link-text">Members</span>
                                                        </a>
                                                    </li>
                                                    <li class="kt-nav__item">
                                                        <a href="#" class="kt-nav__link">
                                                            <i class="kt-nav__link-icon flaticon2-settings"></i>
                                                            <span class="kt-nav__link-text">Settings</span>
                                                        </a>
                                                    </li>
                                                </ul>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="kt-widget2__item kt-widget2__item--danger">
                                        <div class="kt-widget2__checkbox">
                                            <label class="kt-checkbox kt-checkbox--solid kt-checkbox--single">
                                                <input type="checkbox">
                                                <span></span>
                                            </label>
                                        </div>
                                        <div class="kt-widget2__info">
                                            <a href="#" class="kt-widget2__title">
                                                Completa Financial Report For Emirates Airlines
                                            </a>
                                            <a href="#" class="kt-widget2__username">
                                                By Bob
                                            </a>
                                        </div>
                                        <div class="kt-widget2__actions">
                                            <a href="#" class="btn btn-clean btn-sm btn-icon btn-icon-md" data-toggle="dropdown">
                                                <i class="flaticon-more-1"></i>
                                            </a>
                                            <div class="dropdown-menu dropdown-menu-fit dropdown-menu-right">
                                                <ul class="kt-nav">
                                                    <li class="kt-nav__item">
                                                        <a href="#" class="kt-nav__link">
                                                            <i class="kt-nav__link-icon flaticon2-line-chart"></i>
                                                            <span class="kt-nav__link-text">Reports</span>
                                                        </a>
                                                    </li>
                                                    <li class="kt-nav__item">
                                                        <a href="#" class="kt-nav__link">
                                                            <i class="kt-nav__link-icon flaticon2-send"></i>
                                                            <span class="kt-nav__link-text">Messages</span>
                                                        </a>
                                                    </li>
                                                    <li class="kt-nav__item">
                                                        <a href="#" class="kt-nav__link">
                                                            <i class="kt-nav__link-icon flaticon2-pie-chart-1"></i>
                                                            <span class="kt-nav__link-text">Charts</span>
                                                        </a>
                                                    </li>
                                                    <li class="kt-nav__item">
                                                        <a href="#" class="kt-nav__link">
                                                            <i class="kt-nav__link-icon flaticon2-avatar"></i>
                                                            <span class="kt-nav__link-text">Members</span>
                                                        </a>
                                                    </li>
                                                    <li class="kt-nav__item">
                                                        <a href="#" class="kt-nav__link">
                                                            <i class="kt-nav__link-icon flaticon2-settings"></i>
                                                            <span class="kt-nav__link-text">Settings</span>
                                                        </a>
                                                    </li>
                                                </ul>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="kt-widget2__item kt-widget2__item--info">
                                        <div class="kt-widget2__checkbox">
                                            <label class="kt-checkbox kt-checkbox--solid kt-checkbox--single">
                                                <input type="checkbox">
                                                <span></span>
                                            </label>
                                        </div>
                                        <div class="kt-widget2__info">
                                            <a href="#" class="kt-widget2__title">
                                                Completa Financial Report For Emirates Airlines
                                                </p>
                                                <a href="#" class="kt-widget2__username">
                                                    By Sean
                                                </a>
                                        </div>
                                        <div class="kt-widget2__actions">
                                            <a href="#" class="btn btn-clean btn-sm btn-icon btn-icon-md" data-toggle="dropdown">
                                                <i class="flaticon-more-1"></i>
                                            </a>
                                            <div class="dropdown-menu dropdown-menu-fit dropdown-menu-right">
                                                <ul class="kt-nav">
                                                    <li class="kt-nav__item">
                                                        <a href="#" class="kt-nav__link">
                                                            <i class="kt-nav__link-icon flaticon2-line-chart"></i>
                                                            <span class="kt-nav__link-text">Reports</span>
                                                        </a>
                                                    </li>
                                                    <li class="kt-nav__item">
                                                        <a href="#" class="kt-nav__link">
                                                            <i class="kt-nav__link-icon flaticon2-send"></i>
                                                            <span class="kt-nav__link-text">Messages</span>
                                                        </a>
                                                    </li>
                                                    <li class="kt-nav__item">
                                                        <a href="#" class="kt-nav__link">
                                                            <i class="kt-nav__link-icon flaticon2-pie-chart-1"></i>
                                                            <span class="kt-nav__link-text">Charts</span>
                                                        </a>
                                                    </li>
                                                    <li class="kt-nav__item">
                                                        <a href="#" class="kt-nav__link">
                                                            <i class="kt-nav__link-icon flaticon2-avatar"></i>
                                                            <span class="kt-nav__link-text">Members</span>
                                                        </a>
                                                    </li>
                                                    <li class="kt-nav__item">
                                                        <a href="#" class="kt-nav__link">
                                                            <i class="kt-nav__link-icon flaticon2-settings"></i>
                                                            <span class="kt-nav__link-text">Settings</span>
                                                        </a>
                                                    </li>
                                                </ul>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="tab-pane" id="kt_widget2_tab2_content">
                                <div class="kt-widget2">
                                    <div class="kt-widget2__item kt-widget2__item--success">
                                        <div class="kt-widget2__checkbox">
                                            <label class="kt-checkbox kt-checkbox--solid kt-checkbox--single">
                                                <input type="checkbox">
                                                <span></span>
                                            </label>
                                        </div>
                                        <div class="kt-widget2__info">
                                            <a href="#" class="kt-widget2__title">
                                                Make Metronic Great Again.Lorem Ipsum
                                            </a>
                                            <a class="kt-widget2__username">
                                                By James
                                            </a>
                                        </div>
                                        <div class="kt-widget2__actions">
                                            <a href="#" class="btn btn-clean btn-sm btn-icon btn-icon-md" data-toggle="dropdown">
                                                <i class="flaticon-more-1"></i>
                                            </a>
                                            <div class="dropdown-menu dropdown-menu-fit dropdown-menu-right">
                                                <ul class="kt-nav">
                                                    <li class="kt-nav__item">
                                                        <a href="#" class="kt-nav__link">
                                                            <i class="kt-nav__link-icon flaticon2-line-chart"></i>
                                                            <span class="kt-nav__link-text">Reports</span>
                                                        </a>
                                                    </li>
                                                    <li class="kt-nav__item">
                                                        <a href="#" class="kt-nav__link">
                                                            <i class="kt-nav__link-icon flaticon2-send"></i>
                                                            <span class="kt-nav__link-text">Messages</span>
                                                        </a>
                                                    </li>
                                                    <li class="kt-nav__item">
                                                        <a href="#" class="kt-nav__link">
                                                            <i class="kt-nav__link-icon flaticon2-pie-chart-1"></i>
                                                            <span class="kt-nav__link-text">Charts</span>
                                                        </a>
                                                    </li>
                                                    <li class="kt-nav__item">
                                                        <a href="#" class="kt-nav__link">
                                                            <i class="kt-nav__link-icon flaticon2-avatar"></i>
                                                            <span class="kt-nav__link-text">Members</span>
                                                        </a>
                                                    </li>
                                                    <li class="kt-nav__item">
                                                        <a href="#" class="kt-nav__link">
                                                            <i class="kt-nav__link-icon flaticon2-settings"></i>
                                                            <span class="kt-nav__link-text">Settings</span>
                                                        </a>
                                                    </li>
                                                </ul>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="kt-widget2__item kt-widget2__item--warning">
                                        <div class="kt-widget2__checkbox">
                                            <label class="kt-checkbox kt-checkbox--solid kt-checkbox--single">
                                                <input type="checkbox">
                                                <span></span>
                                            </label>
                                        </div>
                                        <div class="kt-widget2__info">
                                            <a href="#" class="kt-widget2__title">
                                                Prepare Docs For Metting On Monday
                                            </a>
                                            <a href="#" class="kt-widget2__username">
                                                By Sean
                                            </a>
                                        </div>
                                        <div class="kt-widget2__actions">
                                            <a href="#" class="btn btn-clean btn-sm btn-icon btn-icon-md" data-toggle="dropdown">
                                                <i class="flaticon-more-1"></i>
                                            </a>
                                            <div class="dropdown-menu dropdown-menu-fit dropdown-menu-right">
                                                <ul class="kt-nav">
                                                    <li class="kt-nav__item">
                                                        <a href="#" class="kt-nav__link">
                                                            <i class="kt-nav__link-icon flaticon2-line-chart"></i>
                                                            <span class="kt-nav__link-text">Reports</span>
                                                        </a>
                                                    </li>
                                                    <li class="kt-nav__item">
                                                        <a href="#" class="kt-nav__link">
                                                            <i class="kt-nav__link-icon flaticon2-send"></i>
                                                            <span class="kt-nav__link-text">Messages</span>
                                                        </a>
                                                    </li>
                                                    <li class="kt-nav__item">
                                                        <a href="#" class="kt-nav__link">
                                                            <i class="kt-nav__link-icon flaticon2-pie-chart-1"></i>
                                                            <span class="kt-nav__link-text">Charts</span>
                                                        </a>
                                                    </li>
                                                    <li class="kt-nav__item">
                                                        <a href="#" class="kt-nav__link">
                                                            <i class="kt-nav__link-icon flaticon2-avatar"></i>
                                                            <span class="kt-nav__link-text">Members</span>
                                                        </a>
                                                    </li>
                                                    <li class="kt-nav__item">
                                                        <a href="#" class="kt-nav__link">
                                                            <i class="kt-nav__link-icon flaticon2-settings"></i>
                                                            <span class="kt-nav__link-text">Settings</span>
                                                        </a>
                                                    </li>
                                                </ul>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="kt-widget2__item kt-widget2__item--danger">
                                        <div class="kt-widget2__checkbox">
                                            <label class="kt-checkbox kt-checkbox--solid kt-checkbox--single">
                                                <input type="checkbox">
                                                <span></span>
                                            </label>
                                        </div>
                                        <div class="kt-widget2__info">
                                            <a href="#" class="kt-widget2__title">
                                                Completa Financial Report For Emirates Airlines
                                            </a>
                                            <a href="#" class="kt-widget2__username">
                                                By Bob
                                            </a>
                                        </div>
                                        <div class="kt-widget2__actions">
                                            <a href="#" class="btn btn-clean btn-sm btn-icon btn-icon-md" data-toggle="dropdown">
                                                <i class="flaticon-more-1"></i>
                                            </a>
                                            <div class="dropdown-menu dropdown-menu-fit dropdown-menu-right">
                                                <ul class="kt-nav">
                                                    <li class="kt-nav__item">
                                                        <a href="#" class="kt-nav__link">
                                                            <i class="kt-nav__link-icon flaticon2-line-chart"></i>
                                                            <span class="kt-nav__link-text">Reports</span>
                                                        </a>
                                                    </li>
                                                    <li class="kt-nav__item">
                                                        <a href="#" class="kt-nav__link">
                                                            <i class="kt-nav__link-icon flaticon2-send"></i>
                                                            <span class="kt-nav__link-text">Messages</span>
                                                        </a>
                                                    </li>
                                                    <li class="kt-nav__item">
                                                        <a href="#" class="kt-nav__link">
                                                            <i class="kt-nav__link-icon flaticon2-pie-chart-1"></i>
                                                            <span class="kt-nav__link-text">Charts</span>
                                                        </a>
                                                    </li>
                                                    <li class="kt-nav__item">
                                                        <a href="#" class="kt-nav__link">
                                                            <i class="kt-nav__link-icon flaticon2-avatar"></i>
                                                            <span class="kt-nav__link-text">Members</span>
                                                        </a>
                                                    </li>
                                                    <li class="kt-nav__item">
                                                        <a href="#" class="kt-nav__link">
                                                            <i class="kt-nav__link-icon flaticon2-settings"></i>
                                                            <span class="kt-nav__link-text">Settings</span>
                                                        </a>
                                                    </li>
                                                </ul>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="kt-widget2__item kt-widget2__item--primary">
                                        <div class="kt-widget2__checkbox">
                                            <label class="kt-checkbox kt-checkbox--solid kt-checkbox--single">
                                                <input type="checkbox">
                                                <span></span>
                                            </label>
                                        </div>
                                        <div class="kt-widget2__info">
                                            <a href="#" class="kt-widget2__title">
                                                Make Metronic Great Again.Lorem Ipsum Amet
                                            </a>
                                            <a href="#" class="kt-widget2__username">
                                                By Bob
                                            </a>
                                        </div>
                                        <div class="kt-widget2__actions">
                                            <a href="#" class="btn btn-clean btn-sm btn-icon btn-icon-md" data-toggle="dropdown">
                                                <i class="flaticon-more-1"></i>
                                            </a>
                                            <div class="dropdown-menu dropdown-menu-fit dropdown-menu-right">
                                                <ul class="kt-nav">
                                                    <li class="kt-nav__item">
                                                        <a href="#" class="kt-nav__link">
                                                            <i class="kt-nav__link-icon flaticon2-line-chart"></i>
                                                            <span class="kt-nav__link-text">Reports</span>
                                                        </a>
                                                    </li>
                                                    <li class="kt-nav__item">
                                                        <a href="#" class="kt-nav__link">
                                                            <i class="kt-nav__link-icon flaticon2-send"></i>
                                                            <span class="kt-nav__link-text">Messages</span>
                                                        </a>
                                                    </li>
                                                    <li class="kt-nav__item">
                                                        <a href="#" class="kt-nav__link">
                                                            <i class="kt-nav__link-icon flaticon2-pie-chart-1"></i>
                                                            <span class="kt-nav__link-text">Charts</span>
                                                        </a>
                                                    </li>
                                                    <li class="kt-nav__item">
                                                        <a href="#" class="kt-nav__link">
                                                            <i class="kt-nav__link-icon flaticon2-avatar"></i>
                                                            <span class="kt-nav__link-text">Members</span>
                                                        </a>
                                                    </li>
                                                    <li class="kt-nav__item">
                                                        <a href="#" class="kt-nav__link">
                                                            <i class="kt-nav__link-icon flaticon2-settings"></i>
                                                            <span class="kt-nav__link-text">Settings</span>
                                                        </a>
                                                    </li>
                                                </ul>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="kt-widget2__item kt-widget2__item--info">
                                        <div class="kt-widget2__checkbox">
                                            <label class="kt-checkbox kt-checkbox--solid kt-checkbox--single">
                                                <input type="checkbox">
                                                <span></span>
                                            </label>
                                        </div>
                                        <div class="kt-widget2__info">
                                            <a href="#" class="kt-widget2__title">
                                                Completa Financial Report For Emirates Airlines
                                            </a>
                                            <a href="#" class="kt-widget2__username">
                                                By Sean
                                            </a>
                                        </div>
                                        <div class="kt-widget2__actions">
                                            <a href="#" class="btn btn-clean btn-sm btn-icon btn-icon-md" data-toggle="dropdown">
                                                <i class="flaticon-more-1"></i>
                                            </a>
                                            <div class="dropdown-menu dropdown-menu-fit dropdown-menu-right">
                                                <ul class="kt-nav">
                                                    <li class="kt-nav__item">
                                                        <a href="#" class="kt-nav__link">
                                                            <i class="kt-nav__link-icon flaticon2-line-chart"></i>
                                                            <span class="kt-nav__link-text">Reports</span>
                                                        </a>
                                                    </li>
                                                    <li class="kt-nav__item">
                                                        <a href="#" class="kt-nav__link">
                                                            <i class="kt-nav__link-icon flaticon2-send"></i>
                                                            <span class="kt-nav__link-text">Messages</span>
                                                        </a>
                                                    </li>
                                                    <li class="kt-nav__item">
                                                        <a href="#" class="kt-nav__link">
                                                            <i class="kt-nav__link-icon flaticon2-pie-chart-1"></i>
                                                            <span class="kt-nav__link-text">Charts</span>
                                                        </a>
                                                    </li>
                                                    <li class="kt-nav__item">
                                                        <a href="#" class="kt-nav__link">
                                                            <i class="kt-nav__link-icon flaticon2-avatar"></i>
                                                            <span class="kt-nav__link-text">Members</span>
                                                        </a>
                                                    </li>
                                                    <li class="kt-nav__item">
                                                        <a href="#" class="kt-nav__link">
                                                            <i class="kt-nav__link-icon flaticon2-settings"></i>
                                                            <span class="kt-nav__link-text">Settings</span>
                                                        </a>
                                                    </li>
                                                </ul>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="kt-widget2__item kt-widget2__item--brand">
                                        <div class="kt-widget2__checkbox">
                                            <label class="kt-checkbox kt-checkbox--solid kt-checkbox--single">
                                                <input type="checkbox">
                                                <span></span>
                                            </label>
                                        </div>
                                        <div class="kt-widget2__info">
                                            <a href="#" class="kt-widget2__title">
                                                Make Widgets Great Again.Estudiat Communy Elit
                                                </p>
                                                <a href="#" class="kt-widget2__username">
                                                    By Aziko
                                                </a>
                                        </div>
                                        <div class="kt-widget2__actions">
                                            <a href="#" class="btn btn-clean btn-sm btn-icon btn-icon-md" data-toggle="dropdown">
                                                <i class="flaticon-more-1"></i>
                                            </a>
                                            <div class="dropdown-menu dropdown-menu-fit dropdown-menu-right">
                                                <ul class="kt-nav">
                                                    <li class="kt-nav__item">
                                                        <a href="#" class="kt-nav__link">
                                                            <i class="kt-nav__link-icon flaticon2-line-chart"></i>
                                                            <span class="kt-nav__link-text">Reports</span>
                                                        </a>
                                                    </li>
                                                    <li class="kt-nav__item">
                                                        <a href="#" class="kt-nav__link">
                                                            <i class="kt-nav__link-icon flaticon2-send"></i>
                                                            <span class="kt-nav__link-text">Messages</span>
                                                        </a>
                                                    </li>
                                                    <li class="kt-nav__item">
                                                        <a href="#" class="kt-nav__link">
                                                            <i class="kt-nav__link-icon flaticon2-pie-chart-1"></i>
                                                            <span class="kt-nav__link-text">Charts</span>
                                                        </a>
                                                    </li>
                                                    <li class="kt-nav__item">
                                                        <a href="#" class="kt-nav__link">
                                                            <i class="kt-nav__link-icon flaticon2-avatar"></i>
                                                            <span class="kt-nav__link-text">Members</span>
                                                        </a>
                                                    </li>
                                                    <li class="kt-nav__item">
                                                        <a href="#" class="kt-nav__link">
                                                            <i class="kt-nav__link-icon flaticon2-settings"></i>
                                                            <span class="kt-nav__link-text">Settings</span>
                                                        </a>
                                                    </li>
                                                </ul>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="tab-pane" id="kt_widget2_tab3_content">
                                <div class="kt-widget2">
                                    <div class="kt-widget2__item kt-widget2__item--warning">
                                        <div class="kt-widget2__checkbox">
                                            <label class="kt-checkbox kt-checkbox--solid kt-checkbox--single">
                                                <input type="checkbox">
                                                <span></span>
                                            </label>
                                        </div>
                                        <div class="kt-widget2__info">
                                            <a href="#" class="kt-widget2__title">
                                                Make Metronic Great Again.Lorem Ipsum
                                            </a>
                                            <a class="kt-widget2__username">
                                                By James
                                            </a>
                                        </div>
                                        <div class="kt-widget2__actions">
                                            <a href="#" class="btn btn-clean btn-sm btn-icon btn-icon-md" data-toggle="dropdown">
                                                <i class="flaticon-more-1"></i>
                                            </a>
                                            <div class="dropdown-menu dropdown-menu-fit dropdown-menu-right">
                                                <ul class="kt-nav">
                                                    <li class="kt-nav__item">
                                                        <a href="#" class="kt-nav__link">
                                                            <i class="kt-nav__link-icon flaticon2-line-chart"></i>
                                                            <span class="kt-nav__link-text">Reports</span>
                                                        </a>
                                                    </li>
                                                    <li class="kt-nav__item">
                                                        <a href="#" class="kt-nav__link">
                                                            <i class="kt-nav__link-icon flaticon2-send"></i>
                                                            <span class="kt-nav__link-text">Messages</span>
                                                        </a>
                                                    </li>
                                                    <li class="kt-nav__item">
                                                        <a href="#" class="kt-nav__link">
                                                            <i class="kt-nav__link-icon flaticon2-pie-chart-1"></i>
                                                            <span class="kt-nav__link-text">Charts</span>
                                                        </a>
                                                    </li>
                                                    <li class="kt-nav__item">
                                                        <a href="#" class="kt-nav__link">
                                                            <i class="kt-nav__link-icon flaticon2-avatar"></i>
                                                            <span class="kt-nav__link-text">Members</span>
                                                        </a>
                                                    </li>
                                                    <li class="kt-nav__item">
                                                        <a href="#" class="kt-nav__link">
                                                            <i class="kt-nav__link-icon flaticon2-settings"></i>
                                                            <span class="kt-nav__link-text">Settings</span>
                                                        </a>
                                                    </li>
                                                </ul>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="kt-widget2__item kt-widget2__item--danger">
                                        <div class="kt-widget2__checkbox">
                                            <label class="kt-checkbox kt-checkbox--solid kt-checkbox--single">
                                                <input type="checkbox">
                                                <span></span>
                                            </label>
                                        </div>
                                        <div class="kt-widget2__info">
                                            <a href="#" class="kt-widget2__title">
                                                Completa Financial Report For Emirates Airlines
                                            </a>
                                            <a href="#" class="kt-widget2__username">
                                                By Bob
                                            </a>
                                        </div>
                                        <div class="kt-widget2__actions">
                                            <a href="#" class="btn btn-clean btn-sm btn-icon btn-icon-md" data-toggle="dropdown">
                                                <i class="flaticon-more-1"></i>
                                            </a>
                                            <div class="dropdown-menu dropdown-menu-fit dropdown-menu-right">
                                                <ul class="kt-nav">
                                                    <li class="kt-nav__item">
                                                        <a href="#" class="kt-nav__link">
                                                            <i class="kt-nav__link-icon flaticon2-line-chart"></i>
                                                            <span class="kt-nav__link-text">Reports</span>
                                                        </a>
                                                    </li>
                                                    <li class="kt-nav__item">
                                                        <a href="#" class="kt-nav__link">
                                                            <i class="kt-nav__link-icon flaticon2-send"></i>
                                                            <span class="kt-nav__link-text">Messages</span>
                                                        </a>
                                                    </li>
                                                    <li class="kt-nav__item">
                                                        <a href="#" class="kt-nav__link">
                                                            <i class="kt-nav__link-icon flaticon2-pie-chart-1"></i>
                                                            <span class="kt-nav__link-text">Charts</span>
                                                        </a>
                                                    </li>
                                                    <li class="kt-nav__item">
                                                        <a href="#" class="kt-nav__link">
                                                            <i class="kt-nav__link-icon flaticon2-avatar"></i>
                                                            <span class="kt-nav__link-text">Members</span>
                                                        </a>
                                                    </li>
                                                    <li class="kt-nav__item">
                                                        <a href="#" class="kt-nav__link">
                                                            <i class="kt-nav__link-icon flaticon2-settings"></i>
                                                            <span class="kt-nav__link-text">Settings</span>
                                                        </a>
                                                    </li>
                                                </ul>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="kt-widget2__item kt-widget2__item--brand">
                                        <div class="kt-widget2__checkbox">
                                            <label class="kt-checkbox kt-checkbox--solid kt-checkbox--single">
                                                <input type="checkbox">
                                                <span></span>
                                            </label>
                                        </div>
                                        <div class="kt-widget2__info">
                                            <a href="#" class="kt-widget2__title">
                                                Make Widgets Great Again.Estudiat Communy Elit
                                            </a>
                                            <a href="#" class="kt-widget2__username">
                                                By Aziko
                                            </a>
                                        </div>
                                        <div class="kt-widget2__actions">
                                            <a href="#" class="btn btn-clean btn-sm btn-icon btn-icon-md" data-toggle="dropdown">
                                                <i class="flaticon-more-1"></i>
                                            </a>
                                            <div class="dropdown-menu dropdown-menu-fit dropdown-menu-right">
                                                <ul class="kt-nav">
                                                    <li class="kt-nav__item">
                                                        <a href="#" class="kt-nav__link">
                                                            <i class="kt-nav__link-icon flaticon2-line-chart"></i>
                                                            <span class="kt-nav__link-text">Reports</span>
                                                        </a>
                                                    </li>
                                                    <li class="kt-nav__item">
                                                        <a href="#" class="kt-nav__link">
                                                            <i class="kt-nav__link-icon flaticon2-send"></i>
                                                            <span class="kt-nav__link-text">Messages</span>
                                                        </a>
                                                    </li>
                                                    <li class="kt-nav__item">
                                                        <a href="#" class="kt-nav__link">
                                                            <i class="kt-nav__link-icon flaticon2-pie-chart-1"></i>
                                                            <span class="kt-nav__link-text">Charts</span>
                                                        </a>
                                                    </li>
                                                    <li class="kt-nav__item">
                                                        <a href="#" class="kt-nav__link">
                                                            <i class="kt-nav__link-icon flaticon2-avatar"></i>
                                                            <span class="kt-nav__link-text">Members</span>
                                                        </a>
                                                    </li>
                                                    <li class="kt-nav__item">
                                                        <a href="#" class="kt-nav__link">
                                                            <i class="kt-nav__link-icon flaticon2-settings"></i>
                                                            <span class="kt-nav__link-text">Settings</span>
                                                        </a>
                                                    </li>
                                                </ul>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="kt-widget2__item kt-widget2__item--info">
                                        <div class="kt-widget2__checkbox">
                                            <label class="kt-checkbox kt-checkbox--solid kt-checkbox--single">
                                                <input type="checkbox">
                                                <span></span>
                                            </label>
                                        </div>
                                        <div class="kt-widget2__info">
                                            <a href="#" class="kt-widget2__title">
                                                Completa Financial Report For Emirates Airlines
                                            </a>
                                            <a href="#" class="kt-widget2__username">
                                                By Sean
                                            </a>
                                        </div>
                                        <div class="kt-widget2__actions">
                                            <a href="#" class="btn btn-clean btn-sm btn-icon btn-icon-md" data-toggle="dropdown">
                                                <i class="flaticon-more-1"></i>
                                            </a>
                                            <div class="dropdown-menu dropdown-menu-fit dropdown-menu-right">
                                                <ul class="kt-nav">
                                                    <li class="kt-nav__item">
                                                        <a href="#" class="kt-nav__link">
                                                            <i class="kt-nav__link-icon flaticon2-line-chart"></i>
                                                            <span class="kt-nav__link-text">Reports</span>
                                                        </a>
                                                    </li>
                                                    <li class="kt-nav__item">
                                                        <a href="#" class="kt-nav__link">
                                                            <i class="kt-nav__link-icon flaticon2-send"></i>
                                                            <span class="kt-nav__link-text">Messages</span>
                                                        </a>
                                                    </li>
                                                    <li class="kt-nav__item">
                                                        <a href="#" class="kt-nav__link">
                                                            <i class="kt-nav__link-icon flaticon2-pie-chart-1"></i>
                                                            <span class="kt-nav__link-text">Charts</span>
                                                        </a>
                                                    </li>
                                                    <li class="kt-nav__item">
                                                        <a href="#" class="kt-nav__link">
                                                            <i class="kt-nav__link-icon flaticon2-avatar"></i>
                                                            <span class="kt-nav__link-text">Members</span>
                                                        </a>
                                                    </li>
                                                    <li class="kt-nav__item">
                                                        <a href="#" class="kt-nav__link">
                                                            <i class="kt-nav__link-icon flaticon2-settings"></i>
                                                            <span class="kt-nav__link-text">Settings</span>
                                                        </a>
                                                    </li>
                                                </ul>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="kt-widget2__item kt-widget2__item--success">
                                        <div class="kt-widget2__checkbox">
                                            <label class="kt-checkbox kt-checkbox--solid kt-checkbox--single">
                                                <input type="checkbox">
                                                <span></span>
                                            </label>
                                        </div>
                                        <div class="kt-widget2__info">
                                            <a href="#" class="kt-widget2__title">
                                                Completa Financial Report For Emirates Airlines
                                            </a>
                                            <a href="#" class="kt-widget2__username">
                                                By Bob
                                            </a>
                                        </div>
                                        <div class="kt-widget2__actions">
                                            <a href="#" class="btn btn-clean btn-sm btn-icon btn-icon-md" data-toggle="dropdown">
                                                <i class="flaticon-more-1"></i>
                                            </a>
                                            <div class="dropdown-menu dropdown-menu-fit dropdown-menu-right">
                                                <ul class="kt-nav">
                                                    <li class="kt-nav__item">
                                                        <a href="#" class="kt-nav__link">
                                                            <i class="kt-nav__link-icon flaticon2-line-chart"></i>
                                                            <span class="kt-nav__link-text">Reports</span>
                                                        </a>
                                                    </li>
                                                    <li class="kt-nav__item">
                                                        <a href="#" class="kt-nav__link">
                                                            <i class="kt-nav__link-icon flaticon2-send"></i>
                                                            <span class="kt-nav__link-text">Messages</span>
                                                        </a>
                                                    </li>
                                                    <li class="kt-nav__item">
                                                        <a href="#" class="kt-nav__link">
                                                            <i class="kt-nav__link-icon flaticon2-pie-chart-1"></i>
                                                            <span class="kt-nav__link-text">Charts</span>
                                                        </a>
                                                    </li>
                                                    <li class="kt-nav__item">
                                                        <a href="#" class="kt-nav__link">
                                                            <i class="kt-nav__link-icon flaticon2-avatar"></i>
                                                            <span class="kt-nav__link-text">Members</span>
                                                        </a>
                                                    </li>
                                                    <li class="kt-nav__item">
                                                        <a href="#" class="kt-nav__link">
                                                            <i class="kt-nav__link-icon flaticon2-settings"></i>
                                                            <span class="kt-nav__link-text">Settings</span>
                                                        </a>
                                                    </li>
                                                </ul>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="kt-widget2__item kt-widget2__item--primary">
                                        <div class="kt-widget2__checkbox">
                                            <label class="kt-checkbox kt-checkbox--solid kt-checkbox--single">
                                                <input type="checkbox">
                                                <span></span>
                                            </label>
                                        </div>
                                        <div class="kt-widget2__info">
                                            <a href="#" class="kt-widget2__title">
                                                Make Metronic Great Again.Lorem Ipsum Amet
                                            </a>
                                            <a href="#" class="kt-widget2__username">
                                                By Bob
                                            </a>
                                        </div>
                                        <div class="kt-widget2__actions">
                                            <a href="#" class="btn btn-clean btn-sm btn-icon btn-icon-md" data-toggle="dropdown">
                                                <i class="flaticon-more-1"></i>
                                            </a>
                                            <div class="dropdown-menu dropdown-menu-fit dropdown-menu-right">
                                                <ul class="kt-nav">
                                                    <li class="kt-nav__item">
                                                        <a href="#" class="kt-nav__link">
                                                            <i class="kt-nav__link-icon flaticon2-line-chart"></i>
                                                            <span class="kt-nav__link-text">Reports</span>
                                                        </a>
                                                    </li>
                                                    <li class="kt-nav__item">
                                                        <a href="#" class="kt-nav__link">
                                                            <i class="kt-nav__link-icon flaticon2-send"></i>
                                                            <span class="kt-nav__link-text">Messages</span>
                                                        </a>
                                                    </li>
                                                    <li class="kt-nav__item">
                                                        <a href="#" class="kt-nav__link">
                                                            <i class="kt-nav__link-icon flaticon2-pie-chart-1"></i>
                                                            <span class="kt-nav__link-text">Charts</span>
                                                        </a>
                                                    </li>
                                                    <li class="kt-nav__item">
                                                        <a href="#" class="kt-nav__link">
                                                            <i class="kt-nav__link-icon flaticon2-avatar"></i>
                                                            <span class="kt-nav__link-text">Members</span>
                                                        </a>
                                                    </li>
                                                    <li class="kt-nav__item">
                                                        <a href="#" class="kt-nav__link">
                                                            <i class="kt-nav__link-icon flaticon2-settings"></i>
                                                            <span class="kt-nav__link-text">Settings</span>
                                                        </a>
                                                    </li>
                                                </ul>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!--end:: Widgets/Tasks -->
            </div>
        </div>
    </div>
</div>

<!--End::App-->
@endsection
