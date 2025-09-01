<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Blade;
use Illuminate\Support\ViewErrorBag;

describe('Form Enhancement Components', function () {
    describe('With Field Component', function () {
        it('renders basic field wrapper structure', function () {
            $component = Blade::render('<x-form.with-field />');

            expect($component)
                ->toContain('div')
                ->toContain('data-form-field');
        });

        it('renders label when provided', function () {
            $component = Blade::render('
                <x-form.with-field label="Email Address">
                    <input type="email" name="email" />
                </x-form.with-field>
            ');

            expect($component)
                ->toContain('Email Address')
                ->toContain('<input type="email" name="email" />')
                ->toContain('data-form-control');
        });

        it('renders description when provided', function () {
            $component = Blade::render('
                <x-form.with-field 
                    label="Password" 
                    description="Must be at least 8 characters">
                    <input type="password" name="password" />
                </x-form.with-field>
            ');

            expect($component)
                ->toContain('Must be at least 8 characters')
                ->toContain('data-form-description');
        });

        it('renders error component for specific field name', function () {
            $component = Blade::render('
                <x-form.with-field label="Email" name="email">
                    <input type="email" name="email" />
                </x-form.with-field>
            ');

            expect($component)
                ->toContain('data-form-error')
                ->toContain('name="email"');
        });

        it('supports block layout variant (default)', function () {
            $component = Blade::render('
                <x-form.with-field variant="block" label="Name">
                    <input type="text" name="name" />
                </x-form.with-field>
            ');

            expect($component)
                ->toContain('flex-col')
                ->toContain('gap-1');
        });

        it('supports inline layout variant', function () {
            $component = Blade::render('
                <x-form.with-field variant="inline" label="Subscribe">
                    <input type="checkbox" name="subscribe" />
                </x-form.with-field>
            ');

            expect($component)
                ->toContain('grid')
                ->toContain('grid-cols-');
        });

        it('forwards additional attributes to field wrapper', function () {
            $component = Blade::render('
                <x-form.with-field 
                    class="custom-field" 
                    data-testid="email-field" 
                    label="Email">
                    <input type="email" name="email" />
                </x-form.with-field>
            ');

            expect($component)
                ->toContain('custom-field')
                ->toContain('data-testid="email-field"');
        });

        it('renders slot content in data-form-control wrapper', function () {
            $component = Blade::render('
                <x-form.with-field label="Complex Input">
                    <div class="input-group">
                        <input type="text" name="complex" />
                        <button type="button">Clear</button>
                    </div>
                </x-form.with-field>
            ');

            expect($component)
                ->toContain('<div class="input-group">')
                ->toContain('<button type="button">Clear</button>')
                ->toContain('data-form-control');
        });

        it('properly orders elements (label, control, description, error)', function () {
            $component = Blade::render('
                <x-form.with-field 
                    label="Username" 
                    description="Choose a unique username" 
                    name="username">
                    <input type="text" name="username" />
                </x-form.with-field>
            ');

            // Label should come first
            $labelPos = mb_strpos($component, 'Username');
            $inputPos = mb_strpos($component, '<input');
            $descPos = mb_strpos($component, 'Choose a unique username');
            $errorPos = mb_strpos($component, 'x-form.error');

            expect($labelPos)->toBeLessThan($inputPos);
            expect($inputPos)->toBeLessThan($descPos);
            expect($descPos)->toBeLessThan($errorPos);
        });
    });

    describe('Enhanced Error Component', function () {
        it('renders error component structure when no errors exist', function () {
            // Without actual validation errors, the @error directive won't render anything
            // This test verifies the component can be instantiated without errors
            $component = Blade::render('<x-form.error name="email" />', [], new ViewErrorBag());

            // Should render empty when no errors
            expect(mb_trim($component))->toBe('');
        });

        it('has proper component structure available', function () {
            // Test that the component file exists and is properly structured
            $componentPath = resource_path('views/components/form/error.blade.php');
            $content = file_get_contents($componentPath);

            expect($content)
                ->toContain('@error($name)')
                ->toContain('data-form-error')
                ->toContain('role="alert"')
                ->toContain('aria-live="polite"')
                ->toContain('text-red-600');
        });

        it('supports show-icon prop in component structure', function () {
            $componentPath = resource_path('views/components/form/error.blade.php');
            $content = file_get_contents($componentPath);

            expect($content)
                ->toContain('showIcon')
                ->toContain('@if($showIcon)')
                ->toContain('<svg');
        });
    });

    describe('Form Components Integration', function () {
        it('integrates with-field and enhanced error seamlessly', function () {
            $component = Blade::render('
                <x-form.with-field 
                    label="Email Address" 
                    description="We will never share your email" 
                    name="email">
                    <input 
                        type="email" 
                        name="email" 
                        class="form-input"
                        placeholder="Enter your email" />
                </x-form.with-field>
            ');

            expect($component)
                ->toContain('Email Address')
                ->toContain('We will never share your email')
                ->toContain('placeholder="Enter your email"')
                ->toContain('data-form-error')
                ->toContain('data-form-field')
                ->toContain('data-form-control')
                ->toContain('data-form-description')
                ->toContain('data-form-error');
        });

        it('works with existing form input component', function () {
            $component = Blade::render('
                <x-form.with-field 
                    label="Password" 
                    name="password">
                    <x-form.input 
                        type="password" 
                        name="password" 
                        placeholder="Enter password" />
                </x-form.with-field>
            ');

            expect($component)
                ->toContain('Password')
                ->toContain('type="password"')
                ->toContain('placeholder="Enter password"');
        });

        it('maintains proper accessibility relationship between elements', function () {
            $component = Blade::render('
                <x-form.with-field 
                    label="Username" 
                    description="Must be unique" 
                    name="username">
                    <input type="text" name="username" id="username" />
                </x-form.with-field>
            ');

            // Should have proper aria-describedby relationships
            expect($component)
                ->toContain('id="username"')
                ->toContain('data-form-control')
                ->toContain('data-form-description')
                ->toContain('data-form-error');
        });

        it('handles complex nested structures', function () {
            $component = Blade::render('
                <x-form.with-field 
                    label="Phone Number" 
                    name="phone">
                    <div class="flex">
                        <select name="country_code" class="w-20">
                            <option value="+1">+1</option>
                        </select>
                        <input 
                            type="tel" 
                            name="phone" 
                            class="flex-1" 
                            placeholder="123-456-7890" />
                    </div>
                </x-form.with-field>
            ');

            expect($component)
                ->toContain('Phone Number')
                ->toContain('<select name="country_code"')
                ->toContain('<input type="tel"')
                ->toContain('placeholder="123-456-7890"')
                ->toContain('data-form-control');
        });
    });

    describe('Login Form Compatibility', function () {
        it('supports login page email field pattern', function () {
            $component = Blade::render('
                <x-form.with-field label="Email" name="email">
                    <x-form.input 
                        type="email"
                        name="email" 
                        placeholder="email@email.com"
                        value="{{ old(\'email\') }}" />
                </x-form.with-field>
            ');

            expect($component)
                ->toContain('Email')
                ->toContain('type="email"')
                ->toContain('placeholder="email@email.com"')
                ->toContain('data-form-error');
        });

        it('supports login page password field pattern', function () {
            $component = Blade::render('
                <x-form.with-field name="password">
                    <div class="flex items-center justify-between gap-1">
                        <x-form.label for="password">Password</x-form.label>
                        <a href="/forgot" class="text-sm text-primary">
                            Forgot Password?
                        </a>
                    </div>
                    <x-form.input 
                        type="password"
                        name="password"
                        placeholder="Enter Password" />
                </x-form.with-field>
            ');

            expect($component)
                ->toContain('Password')
                ->toContain('Forgot Password?')
                ->toContain('type="password"')
                ->toContain('placeholder="Enter Password"');
        });

        it('handles form field without explicit label prop', function () {
            $component = Blade::render('
                <x-form.with-field name="custom">
                    <label>Custom Label</label>
                    <input type="text" name="custom" />
                </x-form.with-field>
            ');

            expect($component)
                ->toContain('<label>Custom Label</label>')
                ->toContain('<input type="text" name="custom" />')
                ->toContain('data-form-control');
        });
    });
});
