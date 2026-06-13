/** @type {import('tailwindcss').Config} */
module.exports = {
    content: ['./app/**/*.{js,jsx,ts,tsx}', './src/**/*.{js,jsx,ts,tsx}'],
    presets: [require('nativewind/preset')],
    theme: {
        extend: {
            colors: {
                primary: '#1A6B3C',
                'primary-light': '#E8F5EE',
                'primary-dark': '#124D2B',
                surface: '#F5F6F8',
                card: '#FFFFFF',
                'text-primary': '#111827',
                'text-secondary': '#6B7280',
                'text-muted': '#9CA3AF',
                'accent-buy': '#1A6B3C',
                'accent-rent': '#2563EB',
                'accent-swap': '#7C3AED',
                success: '#10B981',
                warning: '#F59E0B',
                danger: '#EF4444',
                'eco-gold': '#F59E0B',
            },
            fontFamily: {
                outfit: ['Outfit_400Regular'],
                'outfit-semibold': ['Outfit_600SemiBold'],
                'outfit-bold': ['Outfit_700Bold'],
                inter: ['Inter_400Regular'],
            },
            borderRadius: {
                card: '12px',
                btn: '10px',
            },
        },
    },
    plugins: [],
};
