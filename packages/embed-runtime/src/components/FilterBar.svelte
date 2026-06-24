<script lang="ts">
  import type { ChartPayload } from '../types/chart.js';
  import type { FilterStore } from '../stores/filters.svelte.js';

  interface Props {
    /**
     * The dashboard's first chart drives the filter bar's dimension list.
     * V1 scope: equality filters only on whatever dimensions the first chart
     * declares.
     */
    primaryChart?: ChartPayload | null;
    store: FilterStore;
  }

  const { primaryChart, store }: Props = $props();

  const dimensions = $derived<string[]>(
    Array.isArray(primaryChart?.semantic_query?.dimensions)
      ? (primaryChart!.semantic_query!.dimensions as string[])
      : [],
  );

  let draft = $state<Record<string, string>>({});

  function applyDimension(field: string): void {
    const value = (draft[field] ?? '').trim();
    if (value === '') {
      store.remove(field);
    } else {
      store.set(field, value, '=');
    }
  }

  function clearAll(): void {
    draft = {};
    store.clear();
  }
</script>

{#if dimensions.length === 0}
  <div class="el-filter-bar el-filter-bar--empty">
    <span>No filterable dimensions on this dashboard.</span>
  </div>
{:else}
  <div class="el-filter-bar">
    <span class="el-filter-bar__label">Filters</span>
    {#each dimensions as dimension (dimension)}
      <label class="el-filter-bar__field">
        <span>{dimension}</span>
        <input
          type="text"
          placeholder="="
          bind:value={draft[dimension]}
          onchange={() => applyDimension(dimension)}
          onkeydown={(e) => {
            if (e.key === 'Enter') {
              applyDimension(dimension);
            }
          }}
        />
      </label>
    {/each}
    <button type="button" class="el-filter-bar__clear" onclick={clearAll}>
      Clear
    </button>
  </div>
{/if}

<style>
  .el-filter-bar {
    display: flex;
    flex-wrap: wrap;
    gap: 0.5rem 0.75rem;
    align-items: center;
    padding: 0.5rem 0.75rem;
    background: #f9fafb;
    border: 1px solid #e5e7eb;
    border-radius: 0.5rem;
    font-size: 0.8125rem;
  }
  .el-filter-bar--empty {
    color: #6b7280;
    font-style: italic;
  }
  .el-filter-bar__label {
    font-weight: 600;
    color: #374151;
  }
  .el-filter-bar__field {
    display: inline-flex;
    align-items: center;
    gap: 0.375rem;
    color: #374151;
  }
  .el-filter-bar__field input {
    border: 1px solid #d1d5db;
    border-radius: 0.25rem;
    padding: 0.25rem 0.5rem;
    font-size: 0.8125rem;
    width: 8rem;
  }
  .el-filter-bar__clear {
    margin-left: auto;
    background: transparent;
    border: 1px solid #d1d5db;
    border-radius: 0.25rem;
    padding: 0.25rem 0.5rem;
    cursor: pointer;
    color: #374151;
  }
  .el-filter-bar__clear:hover {
    background: #f3f4f6;
  }
</style>
