import { fileURLToPath, URL } from 'node:url';
import tailwindcss from '@tailwindcss/vite';
import react from '@vitejs/plugin-react';
import { defineConfig } from 'vite';

export default defineConfig({
  plugins: [
    react(),
    tailwindcss(),
  ],
  base: process.env.VITE_BASE_URL || '/',
  resolve: {
    alias: {
      '@': fileURLToPath(new URL('./apps/src', import.meta.url)),
    },
  },
  build: {
    chunkSizeWarningLimit: 3000,
    outDir: 'apps/assets',         // Output to apps/assets folder
    emptyOutDir: true,             // Clean only the assets folder
    manifest: true,                // Generate manifest.json for asset versioning
    rollupOptions: {
      input: 'apps/src/main.jsx',  // From project root
      output: {
        entryFileNames: 'js/[name]-[hash].js',
        chunkFileNames: 'js/[name]-[hash].js',
        assetFileNames: (assetInfo) => {
          const ext = assetInfo.names?.[0]?.split('.').pop() || '';
          if (ext === 'css') {
            return 'css/[name]-[hash][extname]';
          }
          return 'media/[name]-[hash][extname]';
        },
      },
    },
  },
  server: {
    port: 5173,
    strictPort: true,
    cors: true,
    hmr: false,  // Disable HMR to avoid Fast Refresh preamble issues
    // Serve from project root so node_modules is accessible
    fs: {
      strict: false,
    },
  },
});
