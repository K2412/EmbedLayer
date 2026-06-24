/**
 * Mirrors the Laravel SemanticQuery / Filter / TimeDimension DTOs so the
 * runtime can describe what it expects without re-implementing the compiler.
 * Only the shape that round-trips through the embed API is modelled here.
 */

export interface TimeDimension {
  name: string;
  grain: string;
}

export interface Filter {
  field: string;
  operator: string;
  value?: unknown;
  /** Server-side context substitution token, e.g. `embed.external_account_id`. */
  value_from_context?: string;
}

export interface OrderByEntry {
  field: string;
  direction: 'asc' | 'desc' | string;
}

export interface SemanticQuery {
  model: string;
  measures: string[];
  dimensions?: string[];
  time_dimension?: TimeDimension | null;
  filters?: Filter[];
  order_by?: OrderByEntry[];
  limit?: number | null;
  offset?: number | null;
  context?: Record<string, unknown>;
}
