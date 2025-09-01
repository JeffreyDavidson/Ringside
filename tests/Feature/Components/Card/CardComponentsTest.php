<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Blade;

describe('Card Components', function () {
    describe('Card Component', function () {
        it('renders basic card structure', function () {
            $component = Blade::render('<x-card />');

            expect($component)
                ->toContain('div')
                ->toContain('class=')
                ->toContain('card');
        });

        it('applies default styling classes', function () {
            $component = Blade::render('<x-card />');

            expect($component)
                ->toContain('bg-card')
                ->toContain('text-card-foreground')
                ->toContain('border-border')
                ->toContain('flex flex-col')
                ->toContain('border');
        });

        it('renders header slot when provided', function () {
            $component = Blade::render('
                <x-card>
                    <x-slot:header>
                        <h3>Card Header</h3>
                    </x-slot:header>
                </x-card>
            ');

            expect($component)
                ->toContain('<h3>Card Header</h3>')
                ->toContain('card-header');
        });

        it('renders body slot when provided', function () {
            $component = Blade::render('
                <x-card>
                    <x-slot:body>
                        <p>Card body content</p>
                    </x-slot:body>
                </x-card>
            ');

            expect($component)
                ->toContain('<p>Card body content</p>')
                ->toContain('card-body');
        });

        it('renders footer slot when provided', function () {
            $component = Blade::render('
                <x-card>
                    <x-slot:footer>
                        <button>Action</button>
                    </x-slot:footer>
                </x-card>
            ');

            expect($component)
                ->toContain('<button>Action</button>')
                ->toContain('card-footer');
        });

        it('renders default slot content directly', function () {
            $component = Blade::render('
                <x-card>
                    <p>Default slot content</p>
                </x-card>
            ');

            expect($component)
                ->toContain('<p>Default slot content</p>');
        });

        it('supports variant prop for different card styles', function () {
            $bordered = Blade::render('<x-card variant="bordered" />');
            $elevated = Blade::render('<x-card variant="elevated" />');

            expect($bordered)->toContain('border-2');
            expect($elevated)->toContain('shadow-lg');
        });

        it('forwards additional attributes to card element', function () {
            $component = Blade::render('<x-card id="main-card" data-testid="card" />');

            expect($component)
                ->toContain('id="main-card"')
                ->toContain('data-testid="card"');
        });

        it('allows custom classes to be merged', function () {
            $component = Blade::render('<x-card class="custom-card" />');

            expect($component)
                ->toContain('custom-card')
                ->toContain('bg-card');
        });

        it('renders complete card with all slots', function () {
            $component = Blade::render('
                <x-card>
                    <x-slot:header>
                        <h3>Complete Card</h3>
                    </x-slot:header>
                    <x-slot:body>
                        <p>Body content here</p>
                    </x-slot:body>
                    <x-slot:footer>
                        <div class="actions">
                            <button>Save</button>
                            <button>Cancel</button>
                        </div>
                    </x-slot:footer>
                </x-card>
            ');

            expect($component)
                ->toContain('<h3>Complete Card</h3>')
                ->toContain('<p>Body content here</p>')
                ->toContain('<button>Save</button>')
                ->toContain('<button>Cancel</button>')
                ->toContain('card-header')
                ->toContain('card-body')
                ->toContain('card-footer');
        });
    });

    describe('Card Body Component', function () {
        it('renders basic card body structure', function () {
            $component = Blade::render('<x-card.body />');

            expect($component)
                ->toContain('div')
                ->toContain('card-body');
        });

        it('applies consistent padding classes', function () {
            $component = Blade::render('<x-card.body />');

            expect($component)
                ->toMatch('/p-\d+|px-\d+|py-\d+/')
                ->toContain('card-body');
        });

        it('renders slot content', function () {
            $component = Blade::render('
                <x-card.body>
                    <p>Card body content here</p>
                    <div class="content-section">More content</div>
                </x-card.body>
            ');

            expect($component)
                ->toContain('<p>Card body content here</p>')
                ->toContain('<div class="content-section">More content</div>');
        });

        it('forwards additional attributes', function () {
            $component = Blade::render('<x-card.body class="custom-body" data-section="main" />');

            expect($component)
                ->toContain('custom-body')
                ->toContain('data-section="main"')
                ->toContain('card-body');
        });

        it('works properly when nested inside card component', function () {
            $component = Blade::render('
                <x-card>
                    <x-card.body>
                        <p>Nested card body</p>
                    </x-card.body>
                </x-card>
            ');

            expect($component)
                ->toContain('<p>Nested card body</p>')
                ->toContain('card-body');
        });

        it('supports different padding variants', function () {
            $default = Blade::render('<x-card.body />');
            $compact = Blade::render('<x-card.body variant="compact" />');
            $spacious = Blade::render('<x-card.body variant="spacious" />');

            expect($default)->toMatch('/p-6|px-6|py-6/');
            expect($compact)->toMatch('/p-4|px-4|py-4/');
            expect($spacious)->toMatch('/p-8|px-8|py-8/');
        });
    });

    describe('Card Integration', function () {
        it('renders card with explicit card body component', function () {
            $component = Blade::render('
                <x-card>
                    <x-slot:header>
                        <h3>Card with Body Component</h3>
                    </x-slot:header>
                    <x-card.body>
                        <p>Using explicit card body component</p>
                    </x-card.body>
                    <x-slot:footer>
                        <button>Action</button>
                    </x-slot:footer>
                </x-card>
            ');

            expect($component)
                ->toContain('<h3>Card with Body Component</h3>')
                ->toContain('<p>Using explicit card body component</p>')
                ->toContain('<button>Action</button>')
                ->toContain('card-header')
                ->toContain('card-body')
                ->toContain('card-footer');
        });

        it('handles mixed slot and component usage gracefully', function () {
            $component = Blade::render('
                <x-card>
                    <x-slot:header>Header Content</x-slot:header>
                    <x-card.body variant="compact">
                        Body Component Content
                    </x-card.body>
                    Default slot content should also work
                </x-card>
            ');

            expect($component)
                ->toContain('Header Content')
                ->toContain('Body Component Content')
                ->toContain('Default slot content should also work');
        });
    });
});
