/** @type {import('tailwindcss').Config} */
module.exports = {
  content: ['./src/Presentation/Views/**/*.php', './public/**/*.php'],
  theme: {
    extend: {
      colors: {
        ink: '#10283F',
        institutional: { 50: '#EFF7FA', 100: '#DBEDF3', 600: '#176B87', 700: '#12566D', 800: '#10485C' },
        accent: { 50: '#F4F7E9', 600: '#687C2D', 700: '#536523' },
        paper: '#F6F8F9'
      },
      fontFamily: { sans: ['Inter', 'ui-sans-serif', 'system-ui', 'sans-serif'] },
      boxShadow: { card: '0 12px 34px rgba(16, 40, 63, 0.07)' }
    }
  },
  plugins: []
};
