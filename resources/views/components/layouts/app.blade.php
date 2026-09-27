<!DOCTYPE html>
<html class="h-full" lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <meta name="csrf-token" content="{{ csrf_token() }}" />

    <script>
        document.documentElement.setAttribute('data-sidebar-initializing', '');

        try {
            const sidebarExpanded = window.localStorage.getItem('ringside.sidebar.expanded');

            document.documentElement.dataset.sidebarCollapsed = String(sidebarExpanded === 'false');
        } catch {}
    </script>

    <title>{{ \Illuminate\Support\Facades\Config::string('app.name', 'Ringside') }}</title>

    <link
        rel="stylesheet"
        href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&amp;display=swap"
    />
    @vite('resources/js/app.js')
    @livewireStyles

    @stack('scripts')
    @stack('styles')
</head>

<body class="layout1 bg-ringside-surface text-ringside-ink min-h-dvh antialiased">
    <script>
        document.body.style.setProperty(
            '--sidebar-initial-width',
            document.documentElement.dataset.sidebarCollapsed === 'true'
                ? 'var(--sidebar-collapsed-width)'
                : 'var(--sidebar-default-width)',
        );
    </script>

    <!-- Page -->
    <!-- Main -->
    <div class="flex h-dvh min-h-dvh grow overflow-hidden">
        <!-- Sidebar -->
        <x-sidebar />
        <script>
            if (document.documentElement.dataset.sidebarCollapsed === 'true') {
                document.querySelector('aside')?.setAttribute('data-collapsed', 'true');
            }
        </script>
        <!-- End of Sidebar -->
        <!-- Wrapper -->
        <div
            class="flex h-dvh min-h-dvh min-w-0 grow flex-col overflow-hidden pt-[var(--header-height)] transition-[padding] duration-[var(--sidebar-transition-duration)] ease-[var(--sidebar-transition-timing)] lg:ps-[var(--shell-sidebar-width)] lg:pt-[var(--header-height)]"
            x-data
            x-init="$nextTick(() => document.documentElement.removeAttribute('data-sidebar-initializing'))"
            style="--shell-sidebar-width: var(--sidebar-initial-width, var(--sidebar-default-width))"
            :style="$store.sidebar && $store.sidebar.expanded
                ? '--shell-sidebar-width: var(--sidebar-default-width)'
                : '--shell-sidebar-width: var(--sidebar-collapsed-width)'"
            data-test="app-shell-wrapper"
        >
            <!-- Header -->
            <x-layouts.partials.header />
            <!-- End of Header -->
            <x-flash-messages />
            <!-- Content -->
            <main class="min-h-0 min-w-0 grow overflow-y-auto p-4 lg:p-7">{{ $slot }}</main>
            <!-- End of Content -->
            <!-- Footer -->
            @persist('page-footer')
                <x-layouts.partials.footer />
            @endpersist
            <!-- End of Footer -->
        </div>
        <!-- End of Wrapper -->
    </div>
    <!-- End of Main -->
    <!-- End of Page -->
    @livewire('wire-elements-modal')
    @livewireScriptConfig
</body>
</html>
