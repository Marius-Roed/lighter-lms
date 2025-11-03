import { defineConfig } from 'vite'
import { svelte } from '@sveltejs/vite-plugin-svelte'
import path from 'node:path';

export default defineConfig({
  plugins: [
    svelte({
      compilerOptions: {
        runes: true,
      },
    })
  ],
  server: {
    port: 5173,
    hmr: {
      host: 'localhost',
      protocol: 'ws',
    },
    headers: {
      'Access-Control-Allow-Origin': '*',
      'Access-Control-Allow-Methods': 'GET, POST, PUT, DELETE, OPTIONS',
      'Access-Control-Allow-Headers': 'X-Requested-with, Content-Type, Autherization',
    },
    proxy: {
      '/wp-admin/admin-ajax.php': {
        target: 'http://plugin-dev.local',
        changeOrigin: true,
        secure: false,
      },
      '/wp-json': {
        target: 'http://plugin-dev.local',
        changeOrigin: true,
        secure: false
      },
    }
  },
  resolve: {
    alias: {
      '$icons': path.resolve(__dirname, '../icons'),
      '$lib': path.resolve(__dirname, './src/lib'),
      '$components': path.resolve(__dirname, './src/components'),
      '$': path.resolve(__dirname, './src'),
    },
  },
  build: {
    outDir: '../dist',
    manifest: true,
    emptyOutDir: true,
    rollupOptions: {
      input: {
        dashboard: path.resolve(__dirname, 'src/screens/dashboard/main.js'),
        courses: path.resolve(__dirname, 'src/screens/courses/main.js'),
        lessons: path.resolve(__dirname, 'src/screens/lessons/main.js'),
        tablelist: path.resolve(__dirname, 'src/screens/pages/main.js'),
        hooks: path.resolve(__dirname, 'src/hooks.js'),
      },
      output: {
        entryFileNames: (info) => {
          if (info.name === 'hooks') {
            return 'lighterlms-hooks.js';
          }
          return 'js/lighterlms-[name].js';
        },
        chunkFileNames: 'js/chunks/[name]-[hash].js',
        assetFileNames: (info) => {
          const name = info.names?.[0] ?? '';
          if (!name) return 'assets/asset-[hash]';
          const ext = path.extname(name);
          const base = path.basename(name, ext);
          if (ext) {
            return `assets/${base}-[hash]${ext}`;
          }
          return `assets/${base}-[hash]`;
        },
        manualChunks: {
          vendor: ['svelte'],
        }
      }
    }
  }
})
