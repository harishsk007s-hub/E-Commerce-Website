/** @type {import('tailwindcss').Config} */
export default {
  content: [
    "./index.html",
    "./src/**/*.{js,ts,jsx,tsx}",
  ],
  theme: {
    extend: {
      colors: {
        primary: '#FFC222',
        secondary: '#1E1D23',
        background: '#F7F2E2',
        accent: '#AF0F26',
      },
      fontFamily: {
        sans: ['Gilroy', '"Helvetica Neue Light"', '"Helvetica Neue"', 'Helvetica', 'Arial', 'sans-serif'],
        black: ['Gilroy', '"Helvetica Neue Light"', '"Helvetica Neue"', 'Helvetica', 'Arial', 'sans-serif'],
        cursive: ['Yellowtail', 'cursive'],
      },
      animation: {
        'scale-in': 'scaleIn 0.5s ease-out forwards',
        'sweep-up': 'sweepUp 0.7s ease-in-out forwards',
      },
      keyframes: {
        scaleIn: {
          '0%': { transform: 'scale(0.9)', opacity: '0' },
          '100%': { transform: 'scale(1)', opacity: '1' },
        },
        sweepUp: {
          '0%': { transform: 'translateY(100%)' },
          '100%': { transform: 'translateY(0)' },
        },
      },
    },
  },
  plugins: [],
}
