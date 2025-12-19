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
                    50: '#f6f6f4',
                    100: '#edeee9',
                    200: '#dbddd2',
                    300: '#b7baa6',
                    400: '#939879',
                    500: '#4B5320',
                    600: '#444b1d',
                    700: '#3c421a',
                    800: '#353a16',
                    900: '#2d3213',
                    950: '#262a10'
                }
            },
            fontFamily: {
                sans: ['Inter', ...defaultTheme.fontFamily.sans],
            },
        }
    }
}