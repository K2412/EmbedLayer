<script lang="ts">
  import type { ChartPayload, ChartResultPayload } from '../types/chart.js';
  import NumberCard from '../charts/adapters/NumberCard.svelte';
  import BarChart from '../charts/adapters/BarChart.svelte';
  import LineChart from '../charts/adapters/LineChart.svelte';
  import TableChart from '../charts/adapters/TableChart.svelte';

  interface Props {
    chart: ChartPayload;
    /** undefined while loading; null after a failed fetch. */
    result: ChartResultPayload | null | undefined;
    error?: string | null;
  }

  const { chart, result, error = null }: Props = $props();
</script>

<div class="el-chart-card">
  <header class="el-chart-card__header">
    <h3>{chart.name}</h3>
    {#if chart.description}
      <p class="el-chart-card__desc">{chart.description}</p>
    {/if}
  </header>

  <div class="el-chart-card__body">
    {#if error}
      <div class="el-chart-card__error">
        <strong>Could not load chart</strong>
        <p>{error}</p>
      </div>
    {:else if result === undefined}
      <div class="el-skeleton" aria-busy="true" aria-label="Loading chart"></div>
    {:else if result === null}
      <div class="el-chart-card__error">
        <strong>No data returned</strong>
      </div>
    {:else if chart.chart_type === 'number_card'}
      <NumberCard {chart} {result} />
    {:else if chart.chart_type === 'bar_chart'}
      <BarChart {chart} {result} />
    {:else if chart.chart_type === 'line_chart'}
      <LineChart {chart} {result} />
    {:else if chart.chart_type === 'table'}
      <TableChart {chart} {result} />
    {:else}
      <div class="el-chart-card__error">
        <strong>Unsupported chart type</strong>
        <p>{chart.chart_type}</p>
      </div>
    {/if}
  </div>
</div>

<style>
  .el-chart-card {
    background: #ffffff;
    border: 1px solid #e5e7eb;
    border-radius: 0.5rem;
    display: flex;
    flex-direction: column;
    min-height: 10rem;
    box-shadow: 0 1px 2px rgba(0, 0, 0, 0.04);
    overflow: hidden;
  }
  .el-chart-card__header {
    padding: 0.75rem 1rem 0.25rem;
    border-bottom: 1px solid #f3f4f6;
  }
  .el-chart-card__header h3 {
    margin: 0;
    font-size: 0.9375rem;
    font-weight: 600;
    color: #111827;
  }
  .el-chart-card__desc {
    margin: 0.125rem 0 0;
    font-size: 0.75rem;
    color: #6b7280;
  }
  .el-chart-card__body {
    flex: 1;
    display: flex;
    flex-direction: column;
    justify-content: center;
  }
  .el-chart-card__error {
    padding: 1rem;
    color: #b91c1c;
    font-size: 0.875rem;
  }
  .el-skeleton {
    flex: 1;
    margin: 1rem;
    border-radius: 0.375rem;
    background: linear-gradient(90deg, #f3f4f6 0%, #e5e7eb 50%, #f3f4f6 100%);
    background-size: 200% 100%;
    animation: el-skeleton-shimmer 1.4s ease-in-out infinite;
    min-height: 6rem;
  }
  @keyframes el-skeleton-shimmer {
    0% {
      background-position: 200% 0;
    }
    100% {
      background-position: -200% 0;
    }
  }
</style>
