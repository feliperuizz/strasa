/** @type {import('tailwindcss').Config} */
export default {
    darkMode: 'class',
    content: [
        './resources/views/**/*.blade.php',
        './resources/js/**/*.js',
        './app/**/*.php',
    ],
    theme: {
        extend: {
            colors: {
                ink: {
                    900: 'rgb(var(--ink-900) / <alpha-value>)',
                    800: 'rgb(var(--ink-800) / <alpha-value>)',
                    700: 'rgb(var(--ink-700) / <alpha-value>)',
                    600: 'rgb(var(--ink-600) / <alpha-value>)',
                    500: 'rgb(var(--ink-500) / <alpha-value>)',
                },
                slate: {
                    200: 'rgb(var(--text-primary) / <alpha-value>)',
                    300: 'rgb(var(--text-secondary) / <alpha-value>)',
                    400: 'rgb(var(--text-tertiary) / <alpha-value>)',
                    500: 'rgb(var(--ink-500) / <alpha-value>)',
                    600: 'rgb(var(--ink-600) / <alpha-value>)',
                },
                brand: { 400: '#818cf8', 500: '#6366f1', 600: '#4f46e5' },
            },
        },
    },
    plugins: [],
};
