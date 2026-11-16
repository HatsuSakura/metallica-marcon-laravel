const colors = require('tailwindcss/colors');
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
      spacing: {
        18: '4.5rem',
      },
    },
  },
  daisyui: {
    themes: [
      {
        lighttheme: {
          "primary-content": colors.white,
          "base-content": colors.gray[900],
          "base-200": colors.gray[100],
          "base-300": colors.gray[300],
          "neutral-content": colors.gray[900],
          "primary": "#537483", // Main brand color
          "secondary": "#f2d765", // Muted yellow
          "accent": "#3e5a66", // Darker shade of primary for highlights
          "neutral": "#f5f5f5", // Light gray background
          "base-100": "#ffffff", // Pure white for content background
          "info": "#1fb2a9", // Teal for information messages
          "success": "#10b981", // Green for success messages
          "warning": "#f59e0b", // Yellow for warnings
          "error": colors.red[700], // Readable error outlines on light surfaces
          "error-content": colors.white,
        },
        darktheme: {
          "primary": "#8fb4c4", // Same brand hue, lighter for outlined controls in dark mode
          "primary-content": colors.gray[900],
          "base-content": colors.gray[100],
          "base-200": colors.gray[800],
          "base-300": colors.gray[600],
          "neutral-content": colors.gray[100],
          "secondary": "#d9b334", // Darker muted yellow
          "accent": "#263a41", // Even darker shade of primary
          "neutral": "#212529", // Dark gray background
          "base-100": "#111827", // Very dark gray for content background
          "info": "#1c7977", // Darker teal
          "success": "#059669", // Darker green
          "warning": "#d97706", // Amber for warnings
          "error": colors.red[400], // Readable error outlines on dark surfaces
          "error-content": colors.gray[900],
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

