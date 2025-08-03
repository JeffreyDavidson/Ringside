import js from '@eslint/js';
import prettier from 'eslint-plugin-prettier';
import prettierConfig from 'eslint-config-prettier';

export default [
    js.configs.recommended,
    prettierConfig,
    {
        plugins: {
            prettier,
        },
        languageOptions: {
            ecmaVersion: 2022,
            sourceType: 'module',
            globals: {
                Alpine: 'readonly',
                Livewire: 'readonly',
                window: 'readonly',
                document: 'readonly',
                console: 'readonly',
                $store: 'readonly',
                $dispatch: 'readonly',
                $watch: 'readonly',
                $nextTick: 'readonly',
                $el: 'readonly',
                $refs: 'readonly',
                $data: 'readonly',
            },
        },
        rules: {
            'prettier/prettier': 'error',
            'no-unused-vars': 'warn',
            'no-console': 'off', // Allow console for debugging
            'no-undef': 'error',
            
            // Alpine.js specific rules
            'no-implicit-globals': 'error',
            'prefer-const': 'error',
            'no-var': 'error',
            
            // Code quality
            'eqeqeq': 'error',
            'curly': 'error',
            'no-eval': 'error',
            'no-implied-eval': 'error',
            
            // Style preferences
            'prefer-arrow-callback': 'error',
            'prefer-template': 'error',
            'object-shorthand': 'error',
        },
        files: ['**/*.js', '**/*.mjs'],
        ignores: [
            'node_modules/**',
            'vendor/**',
            'public/build/**',
            'bootstrap/cache/**',
            'storage/**',
            '*.min.js',
        ],
    },
    {
        // Blade template-specific rules for Alpine.js
        files: ['resources/views/**/*.blade.php'],
        rules: {
            // These would need a custom parser for PHP/Blade
            // For now, we'll focus on JS files
        },
    },
];