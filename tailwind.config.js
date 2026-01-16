/** @type {import('tailwindcss').Config} */
export default {
  content: [
    './resources/**/*.blade.php',
    './resources/**/*.js',
    './resources/**/*.vue',
    './vendor/livewire/flux/stubs/**/*.blade.php',
    './vendor/livewire/flux-pro/stubs/**/*.blade.php',
  ],
  theme: {
    extend: {},
  },
  plugins: [],
}
