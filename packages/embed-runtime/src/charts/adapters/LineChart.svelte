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
  const validShape = $derived(measureKey !== null && result.rows.length >= 2);

  const points = $derived(
    validShape
      ? result.rows.map((row, i) => ({
          x: i,
          value: asNumber(rowValue(row, measureKey!)) ?? 0,
          label: labelKey ? formatCell(rowValue(row, labelKey)) : `#${i + 1}`,
        }))
      : [],
  );

  const maxValue = $derived(
    points.reduce((acc, p) => (p.value > acc ? p.value : acc), 0),
  );
  const minValue = $derived(
    points.reduce(
      (acc, p) => (p.value < acc ? p.value : acc),
      points[0]?.value ?? 0,
    ),
  );

  const width = 600;
  const height = 180;
  const padX = 24;
  const padY = 16;

  const path = $derived.by(() => {
    if (points.length < 2) {
      return '';
    }
    const range = maxValue - minValue || 1;
    const stepX = (width - padX * 2) / (points.length - 1);
    return points
      .map((p, i) => {
        const x = padX + i * stepX;
        const y = padY + (height - padY * 2) * (1 - (p.value - minValue) / range);
        return `${i === 0 ? 'M' : 'L'}${x.toFixed(2)},${y.toFixed(2)}`;
      })
      .join(' ');
  });
</script>

{#if !validShape}
  <div class="el-error">
    <strong>{chart.name}</strong>
    <p>Line chart needs a numeric column and at least two rows.</p>
  </div>
{:else}
  <div class="el-line-chart">
    <svg viewBox={`0 0 ${width} ${height}`} preserveAspectRatio="none" role="img" aria-label={chart.name}>
      <path d={path} fill="none" stroke="#4f46e5" stroke-width="2" stroke-linejoin="round" stroke-linecap="round" />
    </svg>
    <div class="el-line-chart__legend">
      <span>min {formatCell(minValue)}</span>
      <span>max {formatCell(maxValue)}</span>
    </div>
  </div>
{/if}

<style>
  .el-line-chart {
    padding: 0.75rem;
  }
  .el-line-chart svg {
    width: 100%;
    height: 180px;
    display: block;
  }
  .el-line-chart__legend {
    display: flex;
    justify-content: space-between;
    font-size: 0.75rem;
    color: #6b7280;
    margin-top: 0.25rem;
  }
  .el-error {
    padding: 1rem;
    color: #b91c1c;
    background: #fef2f2;
    border-radius: 0.375rem;
  }
</style>
