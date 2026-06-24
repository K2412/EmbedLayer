# `@embedlayer/runtime`

The Svelte 5 runtime that ships inside the EmbedLayer iframe. Loads a single
JSON payload from `/api/embed/dashboards/{embedId}`, fans out per-chart queries
to `/api/embed/charts/{chartId}/query`, and renders the result with a small set
of minimal-SVG / HTML adapters (number card, bar, line, table).

This is package #2 in the monorepo. The Laravel app lives in the repo root; the
runtime lives here. They share no build tooling.

## Layout

```
src/
  api/client.ts                 # EmbedLayerApiClient + EmbedLayerApiError
  charts/
    adapters/                   # one .svelte per chart_type
    result.ts                   # tiny helpers (formatCell, firstNumericKey…)
  components/
    EmbedLayerDashboard.svelte  # top-level component (load + grid + errors)
    ChartRenderer.svelte        # dispatches a payload to the right adapter
    FilterBar.svelte            # V1 equality-only filter row
  stores/filters.svelte.ts      # Svelte 5 rune-based reactive filter bag
  types/                        # TS mirrors of the Laravel DTOs
  index.ts                      # entry: web component + window.EmbedLayer
```

## Building the bundle

From `packages/embed-runtime/`:

```bash
npm install
npm run build
```

The output is a single self-contained IIFE at `dist/runtime.js` with the Svelte
runtime inlined — no peer dependencies, no module loader required.

## Serving the bundle

The iframe view (`resources/views/embed/dashboard.blade.php`) loads the runtime
from `public/vendor/embedlayer/runtime.js` (relative to the Laravel app). For
self-hosted deployments, copy the built artefact in:

```bash
mkdir -p ../../public/vendor/embedlayer
cp dist/runtime.js ../../public/vendor/embedlayer/runtime.js
```

For the eventual SaaS path, the bundle is mirrored to
`https://cdn.embedlayer.com/runtime.js` and the blade view can be swapped to
point at that URL (see Plan §12 / `el-cd7`).

## Using the runtime

### Web component (default, used by the iframe)

```html
<embed-layer-dashboard
  api-base-url="https://app.example.com/api/embed"
  token="<JWT>"
  embed-id="<embed_id>"
></embed-layer-dashboard>
<script src="/vendor/embedlayer/runtime.js" defer></script>
```

### Programmatic mount

```html
<div id="dashboard"></div>
<script src="/vendor/embedlayer/runtime.js"></script>
<script>
  window.EmbedLayer.renderDashboard('#dashboard', {
    apiBaseUrl: 'https://app.example.com/api/embed',
    token: '<JWT>',
    embedId: '<embed_id>',
  });
</script>
```

## V1 scope notes

- Only four chart types: `number_card`, `bar_chart`, `line_chart`, `table`.
- `FilterBar` only supports equality filters on the dimensions declared by the
  dashboard's first chart. Cross-chart filter targeting is out of scope.
- No charting library — just SVG + HTML. The point of M7 is to prove the
  embed pipeline end-to-end, not ship polished visuals.
