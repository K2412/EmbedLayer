import type { Filter } from '../types/query.js';

/**
 * Tiny reactive filter bag using Svelte 5 runes. Components read `filters`
 * directly and re-derive when it changes; the FilterBar mutates it via the
 * `set`, `clear`, and `remove` helpers.
 */
export class FilterStore {
  filters = $state<Filter[]>([]);

  list(): Filter[] {
    return this.filters;
  }

  set(field: string, value: unknown, operator: string = '='): void {
    const next = this.filters.filter((f) => f.field !== field);
    if (value !== '' && value !== null && value !== undefined) {
      next.push({ field, operator, value });
    }
    this.filters = next;
  }

  remove(field: string): void {
    this.filters = this.filters.filter((f) => f.field !== field);
  }

  clear(): void {
    this.filters = [];
  }
}

export function createFilterStore(): FilterStore {
  return new FilterStore();
}
