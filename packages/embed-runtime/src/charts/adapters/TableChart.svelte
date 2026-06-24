<script lang="ts">
  import type { ChartPayload, ChartResultPayload } from '../../types/chart.js';
  import { formatCell, rowValue } from '../result.js';

  interface Props {
    chart: ChartPayload;
    result: ChartResultPayload;
  }

  const { chart, result }: Props = $props();

  const columns = $derived(
    result.columns.length > 0
      ? result.columns
      : Object.keys(result.rows[0] ?? {}).map((key) => ({
          key,
          label: key,
          type: 'unknown',
        })),
  );
</script>

{#if columns.length === 0}
  <div class="el-error">
    <strong>{chart.name}</strong>
    <p>Table requires at least one column.</p>
  </div>
{:else}
  <div class="el-table-wrap">
    <table class="el-table">
      <thead>
        <tr>
          {#each columns as column (column.key)}
            <th>{column.label}</th>
          {/each}
        </tr>
      </thead>
      <tbody>
        {#each result.rows as row, i (i)}
          <tr>
            {#each columns as column (column.key)}
              <td>{formatCell(rowValue(row, column.key))}</td>
            {/each}
          </tr>
        {/each}
      </tbody>
    </table>
  </div>
{/if}

<style>
  .el-table-wrap {
    overflow-x: auto;
    padding: 0.5rem;
  }
  .el-table {
    width: 100%;
    border-collapse: collapse;
    font-size: 0.875rem;
  }
  .el-table th,
  .el-table td {
    text-align: left;
    padding: 0.5rem 0.75rem;
    border-bottom: 1px solid #e5e7eb;
  }
  .el-table th {
    font-weight: 600;
    color: #374151;
    background: #f9fafb;
  }
  .el-error {
    padding: 1rem;
    color: #b91c1c;
    background: #fef2f2;
    border-radius: 0.375rem;
  }
</style>
