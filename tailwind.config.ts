import type { Config } from 'tailwindcss'

export default {
  content: [
    "./resources/views/*.blade.php",
    "./resources/views/**/*.blade.php",
    "./resources/js/*.js",
    "./app/Livewire/*.php",
    "./app/Livewire/**/*.php",
    "./storage/framework/views/*.php",
  ],
  safelist: [
    'layout1',
    'hidden',
    {
      pattern: /max-w-(sm|md|lg|xl|2xl|3xl|4xl|5xl|6xl|7xl)/,
      variants: ['sm', 'md', 'lg', 'xl', '2xl']
    }
  ],
  theme: {
    extend: {
      fontSize: {
        '3xs': ['0.625rem', { lineHeight: '0.75rem' }],
        '2xs': ['0.6875rem', { lineHeight: '.75rem' }],
        '2sm': ['0.8125rem', { lineHeight: '1.125rem' }],
        md: ['0.9375rem', { lineHeight: '1.375rem' }],
      },
      spacing: {
        '1.25': '.275rem',
        '2.25': '.563rem',
        '4.5': '1.125rem',
        '5.5': '1.375rem',
        '7.5': '1.875rem',
        '8.5': '2.125rem',
      },
      lineHeight: {
        '4.25': '1.125rem'
      },
      fontFamily: {
        sans: ['Inter', 'system-ui', 'sans-serif'],
      },
      screens: {
        sm: '640px',
        md: '768px',
        lg: '1024px',
        xl: '1280px',
        '2xl': '1536px',
      },
      transitionProperty: {
        'width': 'width'
      },
    },
  },
} satisfies Config
