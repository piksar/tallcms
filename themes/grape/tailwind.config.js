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
                    50: '#f8f5fb',
                    100: '#f1eaf6',
                    200: '#e2d5ee',
                    300: '#c5abdc',
                    400: '#a981cb',
                    500: '#6F2DA8',
                    600: '#642997',
                    700: '#592486',
                    800: '#4e2076',
                    900: '#431b65',
                    950: '#381754'
                }
            },
            fontFamily: {
                sans: ['Inter', ...defaultTheme.fontFamily.sans],
            },
        }
    }
}