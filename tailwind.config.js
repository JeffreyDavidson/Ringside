import forms from '@tailwindcss/forms';
import typography from '@tailwindcss/typography';
import aspectRadio from '@tailwindcss/aspect-ratio';
const defaultTheme = import('tailwindcss/defaultTheme.js')

/** @type {import('tailwindcss').Config} */
export default {
    content: [
        "./resources/views/*.blade.php",
        "./resources/views/**/*.blade.php",
        "./resources/js/*.js",
        "./vendor/rappasoft/laravel-livewire-tables/resources/views/*.blade.php",
        "./vendor/rappasoft/laravel-livewire-tables/resources/views/**/*.blade.php",
        "./app/Livewire/*.php",
        "./app/Livewire/**/*.php",
        "./vendor/wire-elements/modal/resources/views/*.blade.php",
        "./storage/framework/views/*.php",
    ],

    theme: {
        extend: {
            colors: {
                gray: {
                    100: '#F9F9F9',
                    200: '#F1F1F4',
                    300: '#DBDFE9',
                    400: '#C4CADA',
                    500: '#99A1B7',
                    600: '#78829D',
                    700: '#4B5675',
                    800: '#252F4A',
                    900: '#071437',
                },
                primary: {
                    DEFAULT: '#1B84FF',
                    active: '#056EE9',
                },
                light: {
                    DEFAULT: '#ffffff',
                    active: '#FCFCFC',
                },
            },
            boxShadows: {
                primary: '0px 4px 12px 0px rgba(40, 132, 239, 0.35)',
            },
            fontSize: {
                '2sm': [
					'0.8125rem',								// 13px
					{
						lineHeight: '1.125rem' 		// 18px
					}
				],
            },
            spacing: {
                '2.25': '.563rem',
                '7.5': '1.875rem',
            },
            lineHeight: {
                '4.25': '1.125rem'
            },
            fontFamily: {
                sans: ['Inter', 'system-ui', 'sans-serif'],
            },
        },
        custom: ({ theme }) => ({
			components: {
				common: {
                    borders: {
						dropdown: '1px solid var(--tw-gray-200)',
					},
                    boxShadows: {
                        dropdown: '0px 7px 18px 0px rgba(0, 0, 0, 0.09)',
					},
                },
            },
        }),
    },

    plugins: [forms, typography, aspectRadio],
};
