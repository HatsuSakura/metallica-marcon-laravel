/** @type {import('tailwindcss').Config} */
export default {
  content: [
    './storage/framework/views/*.php',
    './resources/views/**/*.blade.php',
    './resources/js/**/*.js',
    './resources/js/**/*.vue',
  ],
  theme: {
    extend: {
    },
  },
  daisyui: {
    themes: [
      {
        lighttheme: {
          "primary": "#537483", // Main brand color
          "secondary": "#f2d765", // Muted yellow
          "accent": "#3e5a66", // Darker shade of primary for highlights
          "neutral": "#f5f5f5", // Light gray background
          "base-100": "#ffffff", // Pure white for content background
          "info": "#1fb2a9", // Teal for information messages
          "success": "#10b981", // Green for success messages
          "warning": "#f59e0b", // Yellow for warnings
          "error": "#ef4444", // Red for error messages
        },
        darktheme: {
          "primary": "#3e5a66", // Darker shade of primary
          "secondary": "#d9b334", // Darker muted yellow
          "accent": "#263a41", // Even darker shade of primary
          "neutral": "#212529", // Dark gray background
          "base-100": "#111827", // Very dark gray for content background
          "info": "#1c7977", // Darker teal
          "success": "#059669", // Darker green
          "warning": "#d97706", // Amber for warnings
          "error": "#b91c1c", // Deep red for errors
        },
      },
    ],
    darkMode: ['class', '[data-theme="darktheme"]']  
  },
  plugins: [
    //require('@tailwindcss/forms'),
    require('daisyui'),
  ],
}

