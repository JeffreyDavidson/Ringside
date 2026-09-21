<!DOCTYPE html>
<html class="h-full" lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <meta name="csrf-token" content="{{ csrf_token() }}" />

    <title>{{ \Illuminate\Support\Facades\Config::string('app.name', 'Ringside') }}</title>

    @vite('resources/js/auth.js')
</head>
<!-- end::Head -->

<body class="auth-shell">
    <!--
        THESIS: Ringside is the control room for a wrestling promotion.
        OWN-WORLD: Dark arena surfaces, Anton display type, square controls, and one signal-red action.
        STORY: The brand rail establishes the work; the form gets the promoter back to it.
        FIRST VIEWPORT: A confident split composition with the Ringside wordmark, operating scope, and sign-in task visible together.
        FORM: A short, direct sign-in path with registration, password recovery, remember-me, and readable validation states.
        FINISH: Preserve the editorial rail on desktop, collapse to a focused sign-in screen on mobile, and keep keyboard focus unmistakable.
    -->
    <div class="auth-layout">
        <section class="auth-brand" aria-labelledby="auth-brand-title">
            <a class="auth-wordmark" href="{{ route('home') }}" aria-label="Ringside home"> RING<span>SIDE</span> </a>

            <div class="auth-brand-copy">
                <h1 id="auth-brand-title">Run the show<br /><span>from ringside.</span></h1>
                <p>Keep your roster, titles, events, and match cards moving together from one clear control room.</p>
            </div>

            <div class="auth-brand-meta" aria-label="Ringside product details">
                <span>ROSTER</span>
                <span class="auth-brand-rule" aria-hidden="true"></span>
                <span>EVENTS</span>
                <span class="auth-brand-rule" aria-hidden="true"></span>
                <span>CHAMPIONSHIPS</span>
            </div>
        </section>

        <main class="auth-main">
            <div class="auth-form-wrap">
                <a class="auth-wordmark auth-wordmark-mobile" href="{{ route('home') }}" aria-label="Ringside home">
                    RING<span>SIDE</span>
                </a>
                {{ $slot }}
                <p class="auth-legal">
                    By signing in, you agree to use Ringside responsibly for your promotion's operations.
                </p>
            </div>
        </main>
    </div>
</body>
</html>
