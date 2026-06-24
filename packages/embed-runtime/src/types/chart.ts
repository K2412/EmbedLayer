import type { SemanticQuery } from './query.js';

/** Chart types recognised by the runtime adapters. */
export type ChartType = 'number_card' | 'bar_chart' | 'line_chart' | 'table';

export interface ChartColumn {
  key: string;
  label: string;
  /** Backend tags as `unknown` when not inferred from the semantic model. */
  type: string;
}

export type ChartRow = Record<string, unknown>;

export interface ChartResultPayload {
  columns: ChartColumn[];
  rows: ChartRow[];
  metadata?: Record<string, unknown>;
}

export interface ChartPayload {
  id: string;
  name: string;
  description?: string | null;
  chart_type: ChartType | string;
  options?: Record<string, unknown>;
  semantic_query: SemanticQuery;
  semantic_model?: string | null;
}
