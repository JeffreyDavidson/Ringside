import js from '@eslint/js';

export default [
    js.configs.recommended,
    {
        languageOptions: {
            ecmaVersion: 'latest',
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
                process: 'readonly',
            },
        },
        rules: {
            'no-unused-vars': 'warn',
            'no-console': 'off', // Allow console for debugging
            'no-undef': 'error',

            // Alpine.js specific rules
            'no-implicit-globals': 'error',
            'prefer-const': 'error',
            'no-var': 'error',

            // Code quality
            eqeqeq: 'error',
            curly: 'error',
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
];
