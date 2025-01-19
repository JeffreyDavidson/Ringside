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
    safelist: [
        {
          pattern: /max-w-(sm|md|lg|xl|2xl|3xl|4xl|5xl|6xl|7xl)/,
          variants: ['sm', 'md', 'lg', 'xl', '2xl']
        }
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
                    light: '#EFF6FF',
                    clarity: 'rgba(27, 132, 255, 0.20)',
                    inverse: '#ffffff'
                },
                secondary: {
                    DEFAULT: '#F9F9F9',
                    active: '#f9f9f9',
                    light: '#F9F9F9',
                    clarity: 'rgba(249, 249, 249, 0.20)',
                    inverse: '#4B5675'
                },
                light: {
                    DEFAULT: '#ffffff',
                    active: '#FCFCFC',
                    light: '#ffffff',
                    clarity: 'rgba(255, 255, 255, 0.20)',
                    inverse: '#4B5675'
                },
                danger: {
                    DEFAULT: '#F8285A',
                    active: '#D81A48',
                    light: '#FFEEF3',
                    clarity: 'rgba(248, 40, 90, 0.20)',
                    inverse: '#ffffff'
                },
                warning: {
                    DEFAULT: '#F6B100',
                    active: '#DFA000',
                    light: '#FFF8DD',
                    clarity: 'rgba(246, 177, 0, 0.20)',
                    inverse: '#ffffff'
                },
                info: {
                    DEFAULT: '#7239EA',
                    active: '#5014D0',
                    light: '#F8F5FF',
                    clarity: 'rgba(114, 57, 234, 0.20)',
                    inverse: '#ffffff'
                },
                boxShadows: {
                    default: '0px 4px 12px 0px rgba(0, 0, 0, 0.09)',
                    light: '0px 3px 4px 0px rgba(0, 0, 0, 0.03)',
                    primary: '0px 4px 12px 0px rgba(40, 132, 239, 0.35)',
                    danger: '0px 4px 12px 0px rgba(241, 65, 108, 0.35)',
                    info: '0px 4px 12px 0px rgba(114, 57, 234, 0.35)',
                    warning: '0px 4px 12px 0px rgba(246, 192, 0, 0.35)',
                },
            },
            boxShadow: {
				card: 'var(--tw-card-box-shadow)',
				default: 'var(--tw-default-box-shadow)',
				light: 'var(--tw-light-box-shadow)',
				primary: 'var(--tw-primary-box-shadow)',
				success: 'var(--tw-success-box-shadow)',
				danger: 'var(--tw-danger-box-shadow)',
				info: 'var(--tw-info-box-shadow)',
				warning: 'var(--tw-warning-box-shadow)',
				dark: 'var(--tw-dark-box-shadow)',
			},
            fontSize: {
                '2xs': [
					'0.6875rem',
					{
						lineHeight: '.75rem'
					}
				],
                '2sm': [
					'0.8125rem',
					{
						lineHeight: '1.125rem'
					}
				],
            },
            spacing: {
                '1.25': '.275rem',
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
                        modal: '0px 10px 14px 0px rgba(15, 42, 81, 0.03)',
					},
                },
            },
        }),
    },

    plugins: [forms, typography, aspectRadio],
};
