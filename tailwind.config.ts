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
    'ki-filled',
    'ki-outline', 
    'ki-duotone',
    'ki-solid',
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
        'success': {
          DEFAULT: '#17C653',
          active: '#04B440',
          light: '#EAFFF1',
          clarity: 'rgba(23, 198, 83, 0.20)',
          inverse: '#ffffff',
        },
        'primary': {
          DEFAULT: '#1B84FF',
          active: '#056EE9',
          light: '#EFF6FF', 
          clarity: 'rgba(27, 132, 255, 0.20)',
          inverse: '#ffffff'
        },
        'secondary': {
          DEFAULT: '#F9F9F9',
          active: '#f9f9f9',
          light: '#F9F9F9',
          clarity: 'rgba(249, 249, 249, 0.20)',
          inverse: '#4B5675'
        },
        'light': {
          DEFAULT: '#ffffff',
          active: '#FCFCFC',
          light: '#ffffff',
          clarity: 'rgba(255, 255, 255, 0.20)',
          inverse: '#4B5675'
        },
        'danger': {
          DEFAULT: '#F8285A',
          active: '#D81A48',
          light: '#FFEEF3',
          clarity: 'rgba(248, 40, 90, 0.20)',
          inverse: '#ffffff'
        },
        'warning': {
          DEFAULT: '#F6B100',
          active: '#DFA000',
          light: '#FFF8DD',
          clarity: 'rgba(246, 177, 0, 0.20)',
          inverse: '#ffffff'
        },
        'info': {
          DEFAULT: '#7239EA',
          active: '#5014D0',
          light: '#F8F5FF',
          clarity: 'rgba(114, 57, 234, 0.20)',
          inverse: '#ffffff'
        },
        'dark': {
          DEFAULT: '#1E2129',
          active: '#111318',
          light: '#F9F9F9',
          clarity: 'rgba(30, 33, 41, 0.20)',
          inverse: '#ffffff',
        },
        coal: {
          100: '#15171C',
          200: '#13141A', 
          300: '#111217',
          400: '#0F1014',
          500: '#0D0E12',
          600: '#0B0C10',
          black: '#000000',
          clarity: 'rgba(24, 25, 31, 0.50)',
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
        '3xs': ['0.625rem', { lineHeight: '0.75rem' }],
        '2xs': ['0.6875rem', { lineHeight: '.75rem' }],
        '2sm': ['0.8125rem', { lineHeight: '1.125rem' }],
        md: ['0.9375rem', { lineHeight: '1.375rem' }],
      },
      spacing: {
        '1.25': '.275rem',
        '2.25': '.563rem', 
        '4.5': '1.125rem',  // For Metronic small checkbox (18px)
        '5.5': '1.375rem',  // For Metronic large checkbox (22px)
        '7.5': '1.875rem',
        '8.5': '2.125rem',  // For Metronic medium button height
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