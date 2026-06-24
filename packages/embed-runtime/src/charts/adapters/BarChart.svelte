<script lang="ts">
  import type { ChartPayload, ChartResultPayload } from '../../types/chart.js';
  import {
    asNumber,
    firstNonNumericKey,
    firstNumericKey,
    formatCell,
    rowValue,
  } from '../result.js';

  interface Props {
    chart: ChartPayload;
    result: ChartResultPayload;
  }

  const { chart, result }: Props = $props();

  const measureKey = $derived(firstNumericKey(result));
  const labelKey = $derived(firstNonNumericKey(result));
  const validShape = $derived(measureKey !== null && result.rows.length > 0);

  const bars = $derived(
    validShape
      ? result.rows.map((row, i) => {
          const value = asNumber(rowValue(row, measureKey!)) ?? 0;
          const label = labelKey
            ? formatCell(rowValue(row, labelKey))
            : `#${i + 1}`;
          return { label, value };
        })
      : [],
  );

  const maxValue = $derived(
    bars.reduce((acc, b) => (b.value > acc ? b.value : acc), 0),
  );
</script>

{#if !validShape}
  <div class="el-error">
    <strong>{chart.name}</strong>
    <p>Bar chart requires at least one numeric column and one row.</p>
  </div>
{:else}
  <div class="el-bar-chart">
    {#each bars as bar, i (i)}
      <div class="el-bar-row">
        <span class="el-bar-row__label" title={bar.label}>{bar.label}</span>
        <div class="el-bar-row__track">
          <div
            class="el-bar-row__fill"
            style="width: {maxValue > 0 ? (bar.value / maxValue) * 100 : 0}%"
          ></div>
        </div>
        <span class="el-bar-row__value">{formatCell(bar.value)}</span>
      </div>
    {/each}
  </div>
{/if}

<style>
  .el-bar-chart {
    display: flex;
    flex-direction: column;
    gap: 0.375rem;
    padding: 0.75rem;
  }
  .el-bar-row {
    display: grid;
    grid-template-columns: minmax(0, 9rem) 1fr minmax(0, 6rem);
    align-items: center;
    gap: 0.5rem;
    font-size: 0.8125rem;
  }
  .el-bar-row__label {
    color: #374151;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
  }
  .el-bar-row__track {
    background: #f3f4f6;
    border-radius: 9999px;
    height: 0.5rem;
    overflow: hidden;
  }
  .el-bar-row__fill {
    background: #4f46e5;
    height: 100%;
    transition: width 200ms ease-out;
  }
  .el-bar-row__value {
    text-align: right;
    color: #111827;
    font-variant-numeric: tabular-nums;
  }
  .el-error {
    padding: 1rem;
    color: #b91c1c;
    background: #fef2f2;
    border-radius: 0.375rem;
  }
</style>
