/** @type {import('tailwindcss').Config} */
export default {
    content: [
        './resources/**/*.blade.php',
        './resources/**/*.js',
        './resources/**/*.vue',
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
    ],
    theme: {
        extend: {
            colors: {
                bg: 'var(--bg)',
                surface: 'var(--surface)',
                'shadow-light': 'var(--shadow-light)',
                'shadow-dark': 'var(--shadow-dark)',
                primary: 'var(--primary)',
                'primary-dark': 'var(--primary-dark)',
                'primary-soft': 'var(--primary-soft)',
                text: 'var(--text)',
                muted: 'var(--text-muted)',
                danger: 'var(--danger)',
                warning: 'var(--warning)',
            },
            borderRadius: {
                'neo-lg': 'var(--radius-lg)',
                'neo-md': 'var(--radius-md)',
            },
            boxShadow: {
                'neo-extruded': '8px 8px 16px var(--shadow-dark), -8px -8px 16px var(--shadow-light)',
                'neo-inset': 'inset 4px 4px 8px var(--shadow-dark), inset -4px -4px 8px var(--shadow-light)',
                'neo-inset-sm': 'inset 2px 2px 4px var(--shadow-dark), inset -2px -2px 4px var(--shadow-light)',
                'neo-hover': '12px 12px 24px var(--shadow-dark), -12px -12px 24px var(--shadow-light)',
            },
            transitionTimingFunction: {
                neo: 'var(--ease)',
            },
        },
    },
    plugins: [],
};