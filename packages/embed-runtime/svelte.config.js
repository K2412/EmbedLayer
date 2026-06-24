import { vitePreprocess } from '@sveltejs/vite-plugin-svelte';

/** @type {import('@sveltejs/vite-plugin-svelte').SvelteConfig} */
export default {
  preprocess: vitePreprocess(),
  compilerOptions: {
    // Svelte 5: enable customElement compilation so we can register
    // <embed-layer-dashboard> as a real web component from the bundle.
    customElement: true,
  },
};
