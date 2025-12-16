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
                    50: '#f2f2f9',
                    100: '#e6e6f3',
                    200: '#cccce8',
                    300: '#9999d1',
                    400: '#6666b9',
                    500: '#00008B',
                    600: '#00007d',
                    700: '#00006f',
                    800: '#000061',
                    900: '#000053',
                    950: '#000046'
                }
            },
            fontFamily: {
                sans: ['Inter', ...defaultTheme.fontFamily.sans],
            },
        }
    }
}