/** @type {import('tailwindcss').Config} */
module.exports = {
  content: ['./src/Presentation/Views/**/*.php', './public/**/*.php'],
  theme: {
    extend: {
      colors: {
        ink: '#1D375C',
        institutional: { 50: '#EAF3FA', 100: '#D5E7F4', 600: '#3A699B', 700: '#1D375C', 800: '#162B49' },
        accent: { 50: '#FBF6E6', 600: '#D9AF32', 700: '#715811' },
        paper: '#F5F7FA'
      },
      fontFamily: { sans: ['Inter', 'ui-sans-serif', 'system-ui', 'sans-serif'] },
      boxShadow: { card: '0 12px 34px rgba(29, 55, 92, 0.07)' }
    }
  },
  plugins: []
};
