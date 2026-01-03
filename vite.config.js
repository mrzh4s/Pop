import { defineConfig } from 'vite';
import react from '@vitejs/plugin-react-swc';
import tailwindcss from '@tailwindcss/vite';
import { resolve } from 'path';

export default defineConfig({
  plugins: [
    react(),
    tailwindcss(),
  ],
  resolve: {
    alias: {
      '@': resolve(__dirname, './Infrastructure/Resources/js'),
    },
  },
  build: {
    outDir: 'Infrastructure/Http/Public',
    emptyOutDir: false,
    manifest: true,
    rollupOptions: {
      input: 'Infrastructure/Resources/js/app.jsx',
    },
  },
  server: {
    host: '0.0.0.0',
    port: 5173,
    strictPort: true,
    cors: true,
    hmr: false,  // Disable HMR - prevents preamble errors with custom structure
    // Allow serving files from anywhere in the project
    fs: {
      strict: false,
      allow: ['.'],
    },
  },
  // Optimize dependency pre-bundling
  optimizeDeps: {
    include: ['react', 'react-dom', '@inertiajs/react'],
  },
});