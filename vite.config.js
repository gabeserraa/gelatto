import react from '@vitejs/plugin-react'
import tailwindcss from '@tailwindcss/vite'
import { defineConfig } from 'vite'

// Relative base so the build works under any GitHub Pages repo subpath.
export default defineConfig({
  base: './',
  plugins: [react(), tailwindcss()],
})
