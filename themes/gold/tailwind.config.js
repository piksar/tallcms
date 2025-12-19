import defaultTheme from 'tailwindcss/defaultTheme'

export default {
    content: [
        './resources/views/**/*.blade.php',
        '../../resources/views/**/*.blade.php',
        '../../app/Filament/**/*.php'
    ],
    theme: {
        extend: {
            colors: {
                primary: {
                    50: '#fffdf2',
                    100: '#fffbe6',
                    200: '#fff7cc',
                    300: '#ffef99',
                    400: '#ffe766',
                    500: '#FFD700',
                    600: '#e6c200',
                    700: '#ccac00',
                    800: '#b39700',
                    900: '#998100',
                    950: '#806c00'
                }
            },
            fontFamily: {
                sans: ['Inter', ...defaultTheme.fontFamily.sans],
            },
        }
    }
}