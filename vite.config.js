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
      '@': fileURLToPath(new URL('./templates', import.meta.url)),
    },
  },
  build: {
    chunkSizeWarningLimit: 3000,
    outDir: 'assets',         // Output to apps/assets folder
    emptyOutDir: true,             // Clean only the assets folder
    manifest: true,                // Generate manifest.json for asset versioning
    rollupOptions: {
      input: 'templates/main.jsx',  // From project root
      output: {
        entryFileNames: 'js/[name].js',
        chunkFileNames: 'js/[name].js',
        assetFileNames: (assetInfo) => {
          const ext = assetInfo.names?.[0]?.split('.').pop() || '';
          if (ext === 'css') {
            return 'css/[name][extname]';
          }
          return 'media/[name][extname]';
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
