<script lang="ts">
  import { EmbedLayerApiClient, EmbedLayerApiError } from '../api/client.js';
  import type { DashboardPayload } from '../types/dashboard.js';
  import type { ChartPayload, ChartResultPayload } from '../types/chart.js';
  import { createFilterStore } from '../stores/filters.svelte.js';
  import ChartRenderer from './ChartRenderer.svelte';
  import FilterBar from './FilterBar.svelte';

  interface Props {
    apiBaseUrl: string;
    token: string;
    embedId: string;
    /** Optional override for diagnostics; not used in API calls. */
    dashboardId?: string;
  }

  const { apiBaseUrl, token, embedId, dashboardId }: Props = $props();

  // Top-level dashboard load state.
  let dashboardPayload = $state<DashboardPayload | null>(null);
  let dashboardError = $state<string | null>(null);
  let loading = $state<boolean>(true);

  // Per-chart results, indexed by chart id.
  let chartResults = $state<Record<string, ChartResultPayload | null>>({});
  let chartErrors = $state<Record<string, string | null>>({});

  const filterStore = createFilterStore();

  const primaryChart = $derived<ChartPayload | null>(
    dashboardPayload?.charts[0] ?? null,
  );

  let client = $state<EmbedLayerApiClient | null>(null);

  function ensureClient(): EmbedLayerApiClient | null {
    if (client) {
      return client;
    }
    try {
      client = new EmbedLayerApiClient({
        apiBaseUrl,
        token,
        embedId,
        dashboardId,
      });
      return client;
    } catch (err) {
      dashboardError = (err as Error).message;
      loading = false;
      return null;
    }
  }

  async function loadDashboard(): Promise<void> {
    const c = ensureClient();
    if (!c) {
      return;
    }
    loading = true;
    dashboardError = null;
    try {
      const payload = await c.fetchDashboard();
      dashboardPayload = payload;
      // Kick off every chart query in parallel; we never await them all
      // serially, so a slow chart doesn't block faster ones from rendering.
      await Promise.allSettled(
        payload.charts.map((chart) => loadChart(chart.id)),
      );
    } catch (err) {
      dashboardError =
        err instanceof EmbedLayerApiError
          ? `${err.message} (status ${err.status})`
          : (err as Error).message;
    } finally {
      loading = false;
    }
  }

  async function loadChart(chartId: string): Promise<void> {
    const c = ensureClient();
    if (!c) {
      return;
    }
    // Mark the chart as loading by removing any prior result key.
    chartResults = { ...chartResults };
    delete chartResults[chartId];
    chartErrors = { ...chartErrors, [chartId]: null };
    try {
      const result = await c.runChartQuery(chartId, filterStore.list());
      chartResults = { ...chartResults, [chartId]: result };
    } catch (err) {
      chartErrors = {
        ...chartErrors,
        [chartId]:
          err instanceof EmbedLayerApiError
            ? `${err.message} (status ${err.status})`
            : (err as Error).message,
      };
      chartResults = { ...chartResults, [chartId]: null };
    }
  }

  // Reload all charts whenever the filter set changes (after initial load).
  let lastSeenFilterKey = $state<string>('');
  $effect(() => {
    const key = JSON.stringify(filterStore.list());
    if (!dashboardPayload || key === lastSeenFilterKey) {
      lastSeenFilterKey = key;
      return;
    }
    lastSeenFilterKey = key;
    for (const chart of dashboardPayload.charts) {
      void loadChart(chart.id);
    }
  });

  // Kick off the initial load on mount.
  $effect(() => {
    void loadDashboard();
  });
</script>

<div class="el-dashboard">
  {#if dashboardError}
    <div class="el-dashboard__error" role="alert">
      <strong>Could not load dashboard</strong>
      <p>{dashboardError}</p>
    </div>
  {:else if loading && !dashboardPayload}
    <div class="el-dashboard__loading">
      <div class="el-skeleton el-skeleton--header"></div>
      <div class="el-dashboard__grid">
        {#each [0, 1, 2, 3] as i (i)}
          <div class="el-skeleton el-skeleton--card"></div>
        {/each}
      </div>
    </div>
  {:else if dashboardPayload}
    <header class="el-dashboard__header">
      <h2>{dashboardPayload.dashboard.name}</h2>
      {#if dashboardPayload.dashboard.description}
        <p>{dashboardPayload.dashboard.description}</p>
      {/if}
    </header>

    <FilterBar primaryChart={primaryChart} store={filterStore} />

    {#if dashboardPayload.charts.length === 0}
      <div class="el-dashboard__empty">No charts on this dashboard yet.</div>
    {:else}
      <div class="el-dashboard__grid">
        {#each dashboardPayload.charts as chart (chart.id)}
          <ChartRenderer
            {chart}
            result={chart.id in chartResults ? chartResults[chart.id] : undefined}
            error={chartErrors[chart.id] ?? null}
          />
        {/each}
      </div>
    {/if}
  {/if}
</div>

<style>
  .el-dashboard {
    display: flex;
    flex-direction: column;
    gap: 0.75rem;
    padding: 1rem;
    font-family:
      system-ui,
      -apple-system,
      'Segoe UI',
      Roboto,
      sans-serif;
    color: #111827;
    box-sizing: border-box;
    width: 100%;
  }
  .el-dashboard__header h2 {
    margin: 0;
    font-size: 1.25rem;
    font-weight: 600;
  }
  .el-dashboard__header p {
    margin: 0.25rem 0 0;
    color: #6b7280;
    font-size: 0.875rem;
  }
  .el-dashboard__grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(18rem, 1fr));
    gap: 0.75rem;
  }
  .el-dashboard__error {
    padding: 1rem;
    border-radius: 0.5rem;
    background: #fef2f2;
    color: #991b1b;
  }
  .el-dashboard__empty {
    padding: 2rem;
    text-align: center;
    color: #6b7280;
  }
  .el-skeleton {
    border-radius: 0.5rem;
    background: linear-gradient(90deg, #f3f4f6 0%, #e5e7eb 50%, #f3f4f6 100%);
    background-size: 200% 100%;
    animation: el-skel 1.4s ease-in-out infinite;
  }
  .el-skeleton--header {
    height: 1.5rem;
    width: 12rem;
    margin-bottom: 0.75rem;
  }
  .el-skeleton--card {
    min-height: 10rem;
  }
  @keyframes el-skel {
    0% {
      background-position: 200% 0;
    }
    100% {
      background-position: -200% 0;
    }
  }
</style>
