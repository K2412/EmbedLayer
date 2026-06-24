import { defineConfig } from 'vite';
import { svelte } from '@sveltejs/vite-plugin-svelte';
import { fileURLToPath } from 'node:url';

export default defineConfig({
  plugins: [svelte()],
  build: {
    outDir: 'dist',
    emptyOutDir: true,
    target: 'es2020',
    sourcemap: true,
    minify: 'esbuild',
    lib: {
      entry: fileURLToPath(new URL('./src/index.ts', import.meta.url)),
      name: 'EmbedLayer',
      formats: ['iife'],
      fileName: () => 'runtime.js',
    },
    rollupOptions: {
      // Self-contained bundle — Svelte runtime is inlined so the file can be
      // dropped behind a <script src=…> tag without any peer dependencies.
      output: {
        extend: true,
        inlineDynamicImports: true,
      },
    },
  },
});
