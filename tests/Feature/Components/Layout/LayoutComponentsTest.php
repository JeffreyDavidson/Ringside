<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Blade;

describe('Layout Components', function () {
    describe('Header Component', function () {
        it('renders header with basic structure', function () {
            $component = Blade::render('<x-layout.header />');

            expect($component)
                ->toContain('header')
                ->toContain('class="app-header');
        });

        it('renders logo slot when provided', function () {
            $component = Blade::render('
                <x-layout.header>
                    <x-slot:logo>
                        <img src="/logo.png" alt="Logo" />
                    </x-slot:logo>
                </x-layout.header>
            ');

            expect($component)
                ->toContain('<img src="/logo.png" alt="Logo" />')
                ->toContain('logo');
        });

        it('renders navigation slot when provided', function () {
            $component = Blade::render('
                <x-layout.header>
                    <x-slot:navigation>
                        <nav>
                            <a href="/dashboard">Dashboard</a>
                        </nav>
                    </x-slot:navigation>
                </x-layout.header>
            ');

            expect($component)
                ->toContain('<nav>')
                ->toContain('<a href="/dashboard">Dashboard</a>');
        });

        it('renders action buttons area when provided', function () {
            $component = Blade::render('
                <x-layout.header>
                    <x-slot:actions>
                        <button>Profile</button>
                        <button>Logout</button>
                    </x-slot:actions>
                </x-layout.header>
            ');

            expect($component)
                ->toContain('<button>Profile</button>')
                ->toContain('<button>Logout</button>');
        });

        it('forwards additional attributes to header element', function () {
            $component = Blade::render('<x-layout.header id="main-header" data-test="header" />');

            expect($component)
                ->toContain('id="main-header"')
                ->toContain('data-test="header"');
        });

        it('applies responsive design classes', function () {
            $component = Blade::render('<x-layout.header />');

            expect($component)
                ->toContain('flex')
                ->toContain('items-center')
                ->toMatch('/lg:.*|md:.*|sm:.*/');
        });
    });

    describe('Sidebar Component', function () {
        it('renders sidebar with basic structure', function () {
            $component = Blade::render('<x-layout.sidebar />');

            expect($component)
                ->toContain('aside')
                ->toContain('class="app-sidebar')
                ->toContain('sidebar');
        });

        it('renders with collapsible functionality', function () {
            $component = Blade::render('<x-layout.sidebar />');

            expect($component)
                ->toContain('x-data')
                ->toContain('collapsed')
                ->toMatch('/Alpine\.js|x-show|x-transition/');
        });

        it('renders user profile area when provided', function () {
            $component = Blade::render('
                <x-layout.sidebar>
                    <x-slot:profile>
                        <div class="user-profile">
                            <img src="/avatar.jpg" alt="User" />
                            <span>John Doe</span>
                        </div>
                    </x-slot:profile>
                </x-layout.sidebar>
            ');

            expect($component)
                ->toContain('user-profile')
                ->toContain('<img src="/avatar.jpg" alt="User" />')
                ->toContain('<span>John Doe</span>');
        });

        it('renders menu items when provided', function () {
            $component = Blade::render('
                <x-layout.sidebar>
                    <x-slot:menu>
                        <ul>
                            <li><a href="/dashboard">Dashboard</a></li>
                            <li><a href="/settings">Settings</a></li>
                        </ul>
                    </x-slot:menu>
                </x-layout.sidebar>
            ');

            expect($component)
                ->toContain('<ul>')
                ->toContain('<a href="/dashboard">Dashboard</a>')
                ->toContain('<a href="/settings">Settings</a>');
        });

        it('forwards additional attributes', function () {
            $component = Blade::render('<x-layout.sidebar class="custom-sidebar" data-collapsed="false" />');

            expect($component)
                ->toContain('class="')
                ->toContain('custom-sidebar')
                ->toContain('data-collapsed="false"');
        });

        it('handles mobile responsive behavior', function () {
            $component = Blade::render('<x-layout.sidebar />');

            expect($component)
                ->toMatch('/hidden.*md:block|md:.*|lg:.*/');
        });
    });

    describe('Main Content Wrapper', function () {
        it('renders main content wrapper with basic structure', function () {
            $component = Blade::render('<x-layout.main />');

            expect($component)
                ->toContain('main')
                ->toContain('class="app-main');
        });

        it('renders breadcrumb slot when provided', function () {
            $component = Blade::render('
                <x-layout.main>
                    <x-slot:breadcrumb>
                        <nav aria-label="breadcrumb">
                            <ol>
                                <li><a href="/">Home</a></li>
                                <li>Dashboard</li>
                            </ol>
                        </nav>
                    </x-slot:breadcrumb>
                </x-layout.main>
            ');

            expect($component)
                ->toContain('aria-label="breadcrumb"')
                ->toContain('<a href="/">Home</a>')
                ->toContain('<li>Dashboard</li>');
        });

        it('renders page title when provided', function () {
            $component = Blade::render('
                <x-layout.main title="Dashboard">
                    <p>Content goes here</p>
                </x-layout.main>
            ');

            expect($component)
                ->toContain('Dashboard')
                ->toMatch('/<h[1-6][^>]*>[\s\S]*?Dashboard[\s\S]*?<\/h[1-6]>/');
        });

        it('renders content area with slot content', function () {
            $component = Blade::render('
                <x-layout.main>
                    <div class="dashboard-content">
                        <p>This is the main content</p>
                    </div>
                </x-layout.main>
            ');

            expect($component)
                ->toContain('dashboard-content')
                ->toContain('<p>This is the main content</p>');
        });

        it('applies responsive padding and spacing', function () {
            $component = Blade::render('<x-layout.main />');

            expect($component)
                ->toMatch('/p-.*|px-.*|py-.*/')
                ->toMatch('/space-.*|gap-.*/');
        });

        it('forwards additional attributes', function () {
            $component = Blade::render('<x-layout.main id="main-content" role="main" />');

            expect($component)
                ->toContain('id="main-content"')
                ->toContain('role="main"');
        });
    });

    describe('Grid System Components', function () {
        describe('Container Component', function () {
            it('renders container with basic structure', function () {
                $component = Blade::render('<x-layout.container />');

                expect($component)
                    ->toContain('div')
                    ->toContain('container');
            });

            it('applies responsive max-width classes', function () {
                $component = Blade::render('<x-layout.container />');

                expect($component)
                    ->toMatch('/max-w-.*/')
                    ->toMatch('/mx-auto|container/');
            });

            it('renders content inside container', function () {
                $component = Blade::render('
                    <x-layout.container>
                        <p>Container content</p>
                    </x-layout.container>
                ');

                expect($component)->toContain('<p>Container content</p>');
            });
        });

        describe('Row Component', function () {
            it('renders row with flexbox classes', function () {
                $component = Blade::render('<x-layout.row />');

                expect($component)
                    ->toContain('div')
                    ->toContain('flex')
                    ->toMatch('/flex-wrap|flex-row/');
            });

            it('renders columns inside row', function () {
                $component = Blade::render('
                    <x-layout.row>
                        <x-layout.column>Column 1</x-layout.column>
                        <x-layout.column>Column 2</x-layout.column>
                    </x-layout.row>
                ');

                expect($component)
                    ->toContain('Column 1')
                    ->toContain('Column 2');
            });
        });

        describe('Column Component', function () {
            it('renders column with basic flex classes', function () {
                $component = Blade::render('<x-layout.column />');

                expect($component)
                    ->toContain('div')
                    ->toMatch('/flex-.*|w-.*|basis-.*/');
            });

            it('applies responsive column widths with props', function () {
                $component = Blade::render('<x-layout.column sm="6" md="4" lg="3" />');

                expect($component)
                    ->toMatch('/sm:w-6\/12|sm:basis-6\/12|sm:flex-.*/')
                    ->toMatch('/md:w-4\/12|md:basis-4\/12|md:flex-.*/')
                    ->toMatch('/lg:w-3\/12|lg:basis-3\/12|lg:flex-.*/');
            });

            it('renders content inside column', function () {
                $component = Blade::render('
                    <x-layout.column>
                        <div class="card">Column content</div>
                    </x-layout.column>
                ');

                expect($component)
                    ->toContain('<div class="card">Column content</div>');
            });

            it('forwards additional attributes', function () {
                $component = Blade::render('<x-layout.column class="custom-col" data-col="1" />');

                expect($component)
                    ->toContain('custom-col')
                    ->toContain('data-col="1"');
            });
        });
    });

    describe('Component Integration', function () {
        it('renders complete layout structure', function () {
            $component = Blade::render('
                <x-layout.container>
                    <x-layout.row>
                        <x-layout.column md="12">
                            <x-layout.header>
                                <x-slot:logo>Logo</x-slot:logo>
                                <x-slot:navigation>Nav</x-slot:navigation>
                            </x-layout.header>
                        </x-layout.column>
                    </x-layout.row>
                    <x-layout.row>
                        <x-layout.column md="3">
                            <x-layout.sidebar>
                                <x-slot:menu>Menu</x-slot:menu>
                            </x-layout.sidebar>
                        </x-layout.column>
                        <x-layout.column md="9">
                            <x-layout.main title="Page Title">
                                Main content here
                            </x-layout.main>
                        </x-layout.column>
                    </x-layout.row>
                </x-layout.container>
            ');

            expect($component)
                ->toContain('Logo')
                ->toContain('Nav')
                ->toContain('Menu')
                ->toContain('Page Title')
                ->toContain('Main content here')
                ->toContain('container')
                ->toContain('flex');
        });
    });
});
