import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';

/** @type {import('tailwindcss').Config} */
export default {
    darkMode: 'class',
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './packages/ispos/backoffice/resources/views/**/*.blade.php',
        './packages/ispos/backoffice/resources/js/**/*.vue',
    ],
    theme: {
        extend: {
            fontFamily: {
                sans: ['"DM Sans"', ...defaultTheme.fontFamily.sans],
                display: ['"Plus Jakarta Sans"', ...defaultTheme.fontFamily.sans],
            },
            colors: {
                accent: {
                    DEFAULT: 'rgb(var(--color-accent) / <alpha-value>)',
                    hover: 'rgb(var(--color-accent-hover) / <alpha-value>)',
                    soft: 'rgb(var(--color-accent-soft) / <alpha-value>)',
                    muted: 'rgb(var(--color-accent-muted) / <alpha-value>)',
                },
                surface: {
                    DEFAULT: 'rgb(var(--color-surface) / <alpha-value>)',
                    muted: 'rgb(var(--color-surface-muted) / <alpha-value>)',
                    elevated: 'rgb(var(--color-surface-elevated) / <alpha-value>)',
                },
                ink: {
                    DEFAULT: 'rgb(var(--color-text) / <alpha-value>)',
                    muted: 'rgb(var(--color-text-muted) / <alpha-value>)',
                },
                line: {
                    DEFAULT: 'rgb(var(--color-border) / <alpha-value>)',
                    subtle: 'rgb(var(--color-border-subtle) / <alpha-value>)',
                },
            },
            boxShadow: {
                soft: 'var(--shadow-soft)',
                panel: 'var(--shadow-panel)',
                float: 'var(--shadow-float)',
            },
            borderRadius: {
                xl: 'var(--radius-xl)',
                '2xl': '1.125rem',
            },
            transitionTimingFunction: {
                smooth: 'cubic-bezier(0.4, 0, 0.2, 1)',
            },
        },
    },
    plugins: [forms],
};
