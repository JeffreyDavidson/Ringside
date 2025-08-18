<x-layouts.app>
    <div class="container max-w-6xl mx-auto py-8">
        <!-- Header with Breadcrumb -->
        <div class="mb-8">
            <nav class="flex items-center space-x-2 text-sm text-gray-500 mb-4">
                <a href="{{ route('design-system') }}" class="hover:text-primary">Design System</a>
                <x-ui.icon name="arrow-right" size="sm" class="text-gray-400" />
                <span class="text-gray-900">Buttons</span>
            </nav>
            <h1 class="text-3xl font-bold text-gray-900 mb-4">Button Component</h1>
            <p class="text-lg text-gray-600">Interactive button components with multiple variants, sizes, and comprehensive icon support.</p>
        </div>

        <!-- Button Variants -->
        <section class="mb-16">
            <h2 class="text-2xl font-semibold text-gray-900 mb-8">Variants</h2>
            
            <!-- Primary Buttons -->
            <div class="mb-12">
                <h3 class="text-xl font-medium text-gray-800 mb-6">Primary</h3>
                <div class="flex flex-wrap gap-4 items-center mb-4">
                    <x-ui.button variant="primary">Primary Button</x-ui.button>
                    <x-ui.button variant="primary" iconLeft="ki-plus">With Left Icon</x-ui.button>
                    <x-ui.button variant="primary" iconRight="ki-arrow-right">With Right Icon</x-ui.button>
                    <x-ui.button variant="primary" iconLeft="ki-plus" iconOnly="true" />
                </div>
                <div class="bg-gray-100 p-4 rounded-lg">
                    <code class="text-sm text-gray-800">
                        &lt;x-ui.button variant="primary"&gt;Primary Button&lt;/x-ui.button&gt;<br>
                        &lt;x-ui.button variant="primary" iconLeft="ki-plus"&gt;With Left Icon&lt;/x-ui.button&gt;<br>
                        &lt;x-ui.button variant="primary" iconRight="ki-arrow-right"&gt;With Right Icon&lt;/x-ui.button&gt;<br>
                        &lt;x-ui.button variant="primary" iconLeft="ki-plus" iconOnly="true" /&gt;
                    </code>
                </div>
            </div>

            <!-- Secondary Buttons -->
            <div class="mb-12">
                <h3 class="text-xl font-medium text-gray-800 mb-6">Secondary</h3>
                <div class="flex flex-wrap gap-4 items-center mb-4">
                    <x-ui.button variant="secondary">Secondary Button</x-ui.button>
                    <x-ui.button variant="secondary" iconLeft="ki-pencil">Edit Item</x-ui.button>
                    <x-ui.button variant="secondary" iconRight="ki-arrow-right">Continue</x-ui.button>
                    <x-ui.button variant="secondary" iconLeft="ki-pencil" iconOnly="true" />
                </div>
                <div class="bg-gray-100 p-4 rounded-lg">
                    <code class="text-sm text-gray-800">
                        &lt;x-ui.button variant="secondary"&gt;Secondary Button&lt;/x-ui.button&gt;<br>
                        &lt;x-ui.button variant="secondary" iconLeft="ki-pencil"&gt;Edit Item&lt;/x-ui.button&gt;<br>
                        &lt;x-ui.button variant="secondary" iconRight="ki-arrow-right"&gt;Continue&lt;/x-ui.button&gt;<br>
                        &lt;x-ui.button variant="secondary" iconLeft="ki-pencil" iconOnly="true" /&gt;
                    </code>
                </div>
            </div>

            <!-- Destructive Buttons -->
            <div class="mb-12">
                <h3 class="text-xl font-medium text-gray-800 mb-6">Destructive</h3>
                <div class="flex flex-wrap gap-4 items-center mb-4">
                    <x-ui.button variant="destructive">Delete Item</x-ui.button>
                    <x-ui.button variant="destructive" iconLeft="ki-trash">Remove</x-ui.button>
                    <x-ui.button variant="destructive" iconRight="ki-cross">Cancel</x-ui.button>
                    <x-ui.button variant="destructive" iconLeft="ki-trash" iconOnly="true" />
                </div>
                <div class="bg-gray-100 p-4 rounded-lg">
                    <code class="text-sm text-gray-800">
                        &lt;x-ui.button variant="destructive"&gt;Delete Item&lt;/x-ui.button&gt;<br>
                        &lt;x-ui.button variant="destructive" iconLeft="ki-trash"&gt;Remove&lt;/x-ui.button&gt;<br>
                        &lt;x-ui.button variant="destructive" iconRight="ki-cross"&gt;Cancel&lt;/x-ui.button&gt;<br>
                        &lt;x-ui.button variant="destructive" iconLeft="ki-trash" iconOnly="true" /&gt;
                    </code>
                </div>
            </div>

            <!-- Mono Buttons -->
            <div class="mb-12">
                <h3 class="text-xl font-medium text-gray-800 mb-6">Mono</h3>
                <div class="flex flex-wrap gap-4 items-center mb-4">
                    <x-ui.button variant="mono">Mono Button</x-ui.button>
                    <x-ui.button variant="mono" iconLeft="ki-gear">Settings</x-ui.button>
                    <x-ui.button variant="mono" iconRight="ki-arrow-right">Next</x-ui.button>
                    <x-ui.button variant="mono" iconLeft="ki-gear" iconOnly="true" />
                </div>
                <div class="bg-gray-100 p-4 rounded-lg">
                    <code class="text-sm text-gray-800">
                        &lt;x-ui.button variant="mono"&gt;Mono Button&lt;/x-ui.button&gt;<br>
                        &lt;x-ui.button variant="mono" iconLeft="ki-gear"&gt;Settings&lt;/x-ui.button&gt;<br>
                        &lt;x-ui.button variant="mono" iconRight="ki-arrow-right"&gt;Next&lt;/x-ui.button&gt;<br>
                        &lt;x-ui.button variant="mono" iconLeft="ki-gear" iconOnly="true" /&gt;
                    </code>
                </div>
            </div>

            <!-- Outline Buttons -->
            <div class="mb-12">
                <h3 class="text-xl font-medium text-gray-800 mb-6">Outline</h3>
                <div class="flex flex-wrap gap-4 items-center mb-4">
                    <x-ui.button variant="outline">Outline Button</x-ui.button>
                    <x-ui.button variant="outline" iconLeft="ki-plus">Add Item</x-ui.button>
                    <x-ui.button variant="outline" iconRight="ki-arrow-right">View More</x-ui.button>
                    <x-ui.button variant="outline" iconLeft="ki-plus" iconOnly="true" />
                </div>
                <div class="bg-gray-100 p-4 rounded-lg">
                    <code class="text-sm text-gray-800">
                        &lt;x-ui.button variant="outline"&gt;Outline Button&lt;/x-ui.button&gt;<br>
                        &lt;x-ui.button variant="outline" iconLeft="ki-plus"&gt;Add Item&lt;/x-ui.button&gt;<br>
                        &lt;x-ui.button variant="outline" iconRight="ki-arrow-right"&gt;View More&lt;/x-ui.button&gt;<br>
                        &lt;x-ui.button variant="outline" iconLeft="ki-plus" iconOnly="true" /&gt;
                    </code>
                </div>
            </div>

            <!-- Ghost Buttons -->
            <div class="mb-12">
                <h3 class="text-xl font-medium text-gray-800 mb-6">Ghost</h3>
                <div class="flex flex-wrap gap-4 items-center mb-4">
                    <x-ui.button variant="ghost">Ghost Button</x-ui.button>
                    <x-ui.button variant="ghost" iconLeft="ki-eye">View Details</x-ui.button>
                    <x-ui.button variant="ghost" iconRight="ki-arrow-right">Learn More</x-ui.button>
                    <x-ui.button variant="ghost" iconLeft="ki-eye" iconOnly="true" />
                </div>
                <div class="bg-gray-100 p-4 rounded-lg">
                    <code class="text-sm text-gray-800">
                        &lt;x-ui.button variant="ghost"&gt;Ghost Button&lt;/x-ui.button&gt;<br>
                        &lt;x-ui.button variant="ghost" iconLeft="ki-eye"&gt;View Details&lt;/x-ui.button&gt;<br>
                        &lt;x-ui.button variant="ghost" iconRight="ki-arrow-right"&gt;Learn More&lt;/x-ui.button&gt;<br>
                        &lt;x-ui.button variant="ghost" iconLeft="ki-eye" iconOnly="true" /&gt;
                    </code>
                </div>
            </div>

            <!-- Ghost Colored Variants -->
            <div class="mb-12">
                <h3 class="text-xl font-medium text-gray-800 mb-6">Ghost Colored</h3>
                <div class="flex flex-wrap gap-4 items-center mb-4">
                    <x-ui.button variant="ghost-primary">Ghost Primary</x-ui.button>
                    <x-ui.button variant="ghost-secondary">Ghost Secondary</x-ui.button>
                    <x-ui.button variant="ghost-destructive">Ghost Destructive</x-ui.button>
                    <x-ui.button variant="ghost-mono">Ghost Mono</x-ui.button>
                </div>
                <div class="bg-gray-100 p-4 rounded-lg">
                    <code class="text-sm text-gray-800">
                        &lt;x-ui.button variant="ghost-primary"&gt;Ghost Primary&lt;/x-ui.button&gt;<br>
                        &lt;x-ui.button variant="ghost-secondary"&gt;Ghost Secondary&lt;/x-ui.button&gt;<br>
                        &lt;x-ui.button variant="ghost-destructive"&gt;Ghost Destructive&lt;/x-ui.button&gt;<br>
                        &lt;x-ui.button variant="ghost-mono"&gt;Ghost Mono&lt;/x-ui.button&gt;
                    </code>
                </div>
            </div>
        </section>

        <!-- Button Sizes -->
        <section class="mb-16">
            <h2 class="text-2xl font-semibold text-gray-900 mb-8">Sizes</h2>
            
            <div class="mb-12">
                <h3 class="text-xl font-medium text-gray-800 mb-6">Text Buttons</h3>
                <div class="flex flex-wrap gap-4 items-center mb-4">
                    <x-ui.button variant="primary" size="sm">Small Button</x-ui.button>
                    <x-ui.button variant="primary">Default Button</x-ui.button>
                    <x-ui.button variant="primary" size="lg">Large Button</x-ui.button>
                </div>
                <div class="bg-gray-100 p-4 rounded-lg">
                    <code class="text-sm text-gray-800">
                        &lt;x-ui.button variant="primary" size="sm"&gt;Small Button&lt;/x-ui.button&gt;<br>
                        &lt;x-ui.button variant="primary"&gt;Default Button&lt;/x-ui.button&gt;<br>
                        &lt;x-ui.button variant="primary" size="lg"&gt;Large Button&lt;/x-ui.button&gt;
                    </code>
                </div>
            </div>

            <div class="mb-12">
                <h3 class="text-xl font-medium text-gray-800 mb-6">Icon-Only Buttons</h3>
                <div class="flex flex-wrap gap-4 items-center mb-4">
                    <x-ui.button variant="primary" size="sm" iconLeft="ki-plus" iconOnly="true" />
                    <x-ui.button variant="primary" iconLeft="ki-plus" iconOnly="true" />
                    <x-ui.button variant="primary" size="lg" iconLeft="ki-plus" iconOnly="true" />
                </div>
                <div class="bg-gray-100 p-4 rounded-lg">
                    <code class="text-sm text-gray-800">
                        &lt;x-ui.button variant="primary" size="sm" iconLeft="ki-plus" iconOnly="true" /&gt;<br>
                        &lt;x-ui.button variant="primary" iconLeft="ki-plus" iconOnly="true" /&gt;<br>
                        &lt;x-ui.button variant="primary" size="lg" iconLeft="ki-plus" iconOnly="true" /&gt;
                    </code>
                </div>
            </div>
        </section>

        <!-- Usage Guidelines -->
        <section class="mb-16">
            <h2 class="text-2xl font-semibold text-gray-900 mb-8">Usage Guidelines</h2>
            <div class="bg-blue-50 border border-blue-200 rounded-lg p-6">
                <h3 class="text-lg font-medium text-blue-900 mb-4">Button Component API</h3>
                <div class="space-y-4">
                    <div>
                        <strong class="text-blue-800">variant:</strong>
                        <span class="text-blue-700">primary | secondary | destructive | mono | outline | ghost | ghost-primary | ghost-secondary | ghost-destructive | ghost-mono</span>
                    </div>
                    <div>
                        <strong class="text-blue-800">size:</strong>
                        <span class="text-blue-700">sm | (default) | lg</span>
                    </div>
                    <div>
                        <strong class="text-blue-800">iconLeft:</strong>
                        <span class="text-blue-700">KeenIcons class name (e.g., "ki-plus", "ki-pencil")</span>
                    </div>
                    <div>
                        <strong class="text-blue-800">iconRight:</strong>
                        <span class="text-blue-700">KeenIcons class name (e.g., "ki-arrow-right")</span>
                    </div>
                    <div>
                        <strong class="text-blue-800">iconOnly:</strong>
                        <span class="text-blue-700">boolean - creates square icon-only button</span>
                    </div>
                </div>
                
                <div class="mt-6">
                    <h4 class="font-medium text-blue-900 mb-2">Best Practices:</h4>
                    <ul class="list-disc list-inside space-y-1 text-blue-800">
                        <li>Use <strong>primary</strong> for main call-to-action buttons</li>
                        <li>Use <strong>secondary</strong> for secondary actions</li>
                        <li>Use <strong>destructive</strong> for delete/remove actions</li>
                        <li>Use <strong>outline</strong> for less prominent actions</li>
                        <li>Use <strong>ghost</strong> variants for subtle interactions</li>
                        <li>Icon-only buttons should have tooltips for accessibility</li>
                    </ul>
                </div>
            </div>
        </section>
    </div>
</x-layouts.app>