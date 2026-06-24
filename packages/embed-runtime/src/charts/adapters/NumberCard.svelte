<script lang="ts">
  import type { ChartPayload, ChartResultPayload } from '../../types/chart.js';
  import { asNumber, firstNumericKey, formatCell, rowValue } from '../result.js';

  interface Props {
    chart: ChartPayload;
    result: ChartResultPayload;
  }

  const { chart, result }: Props = $props();

  const numericKey = $derived(firstNumericKey(result));
  const measureLabel = $derived(
    result.columns.find((c) => c.key === numericKey)?.label ?? chart.name,
  );
  const value = $derived(
    numericKey && result.rows.length > 0
      ? asNumber(rowValue(result.rows[0]!, numericKey))
      : null,
  );

  const validShape = $derived(numericKey !== null);
</script>

{#if !validShape}
  <div class="el-error">
    <strong>{chart.name}</strong>
    <p>Number card requires at least one numeric column.</p>
  </div>
{:else}
  <div class="el-number-card">
    <span class="el-number-card__label">{measureLabel}</span>
    <span class="el-number-card__value">{value === null ? '—' : formatCell(value)}</span>
  </div>
{/if}

<style>
  .el-number-card {
    display: flex;
    flex-direction: column;
    gap: 0.25rem;
    padding: 1rem;
  }
  .el-number-card__label {
    font-size: 0.75rem;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    color: #6b7280;
  }
  .el-number-card__value {
    font-size: 2rem;
    font-weight: 600;
    color: #111827;
  }
  .el-error {
    padding: 1rem;
    color: #b91c1c;
    background: #fef2f2;
    border-radius: 0.375rem;
  }
</style>
