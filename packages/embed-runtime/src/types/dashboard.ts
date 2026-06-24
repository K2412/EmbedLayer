import type { ChartPayload } from './chart.js';

export interface EmbedPayload {
  id: string;
  name: string;
  theme: Record<string, unknown>;
}

export interface DashboardSummary {
  id: string;
  name: string;
  slug: string;
  description?: string | null;
  theme?: Record<string, unknown>;
}

export interface DashboardPayload {
  embed: EmbedPayload;
  dashboard: DashboardSummary;
  charts: ChartPayload[];
}
