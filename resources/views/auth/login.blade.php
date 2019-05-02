@extends('layouts.auth')


@section('content')
<div class="kt-login__body">

    <!--begin::Signin-->
    <div class="kt-login__form">
        <div class="kt-login__title">
            <h3>Sign In</h3>
        </div>

        <!--begin::Form-->
        <form class="kt-form" action="{{ route('login') }}" novalidate="novalidate">
            <div class="form-group">
                <input class="form-control" type="text" placeholder="Email" name="email" autocomplete="off">
                @error('email')
                    <span class="invalid-feedback" role="alert">
                        <strong>{{ $message }}</strong>
                    </span>
                @enderror
            </div>
            <div class="form-group">
                <input class="form-control" type="password" placeholder="Password" name="password">
                @error('password')
                    <span class="invalid-feedback" role="alert">
                        <strong>{{ $message }}</strong>
                    </span>
                @enderror
            </div>

            <!--begin::Action-->
            @if (Route::has('password.request'))
                <div class="kt-login__actions">
                    <a href="#" class="kt-link kt-login__link-forgot">
                        Forgot Password ?
                    </a>
                    <button id="kt_login_signin_submit" class="btn btn-primary btn-elevate kt-login__btn-primary">{{ __('Login') }}</button>
                </div>
            @endif

            <!--end::Action-->
        </form>

        <!--end::Form-->

        <!--begin::Divider-->
        <div class="kt-login__divider">
            <div class="kt-divider">
                <span></span>
                <span>OR</span>
                <span></span>
            </div>
        </div>

        <!--end::Divider-->

        <!--begin::Options-->
        <div class="kt-login__options">
            <a href="#" class="btn btn-primary kt-btn">
                <i class="fab fa-facebook-f"></i>
                Facebook
            </a>
            <a href="#" class="btn btn-info kt-btn">
                <i class="fab fa-twitter"></i>
                Twitter
            </a>
            <a href="#" class="btn btn-danger kt-btn">
                <i class="fab fa-google"></i>
                Google
            </a>
        </div>

        <!--end::Options-->
    </div>

    <!--end::Signin-->
</div>
@endsection
