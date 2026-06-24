import type { DashboardPayload } from '../types/dashboard.js';
import type { ChartResultPayload } from '../types/chart.js';
import type { Filter } from '../types/query.js';

export interface EmbedLayerApiClientOptions {
  /** Absolute or relative base URL, e.g. `https://app.example.com/api/embed`. */
  apiBaseUrl: string;
  token: string;
  embedId: string;
  /** Allows pre-fetching dashboard ID for diagnostics. Optional. */
  dashboardId?: string;
  /** Override `fetch` for testing. Defaults to global fetch. */
  fetchImpl?: typeof fetch;
  /** Total retries on 5xx (default 2). */
  maxRetries?: number;
}

export class EmbedLayerApiError extends Error {
  public readonly status: number;
  public readonly body: unknown;

  constructor(status: number, message: string, body?: unknown) {
    super(message);
    this.name = 'EmbedLayerApiError';
    this.status = status;
    this.body = body;
  }
}

/**
 * Minimal embed API client. Calls are issued with `Authorization: Bearer <token>`
 * and a small JSON envelope. 5xx responses are retried with exponential backoff;
 * 4xx responses throw immediately as they indicate an unrecoverable config or
 * token problem.
 */
export class EmbedLayerApiClient {
  private readonly apiBaseUrl: string;
  private readonly token: string;
  private readonly embedId: string;
  private readonly fetchImpl: typeof fetch;
  private readonly maxRetries: number;

  constructor(opts: EmbedLayerApiClientOptions) {
    if (!opts.apiBaseUrl) {
      throw new Error('EmbedLayerApiClient requires apiBaseUrl');
    }
    if (!opts.token) {
      throw new Error('EmbedLayerApiClient requires token');
    }
    if (!opts.embedId) {
      throw new Error('EmbedLayerApiClient requires embedId');
    }
    this.apiBaseUrl = opts.apiBaseUrl.replace(/\/+$/, '');
    this.token = opts.token;
    this.embedId = opts.embedId;
    this.fetchImpl = opts.fetchImpl ?? globalThis.fetch.bind(globalThis);
    this.maxRetries = Math.max(0, opts.maxRetries ?? 2);
  }

  async fetchDashboard(): Promise<DashboardPayload> {
    const url = `${this.apiBaseUrl}/dashboards/${encodeURIComponent(this.embedId)}`;
    return this.request<DashboardPayload>(url, { method: 'GET' });
  }

  async runChartQuery(
    chartId: string,
    filters?: Filter[],
  ): Promise<ChartResultPayload> {
    const url = `${this.apiBaseUrl}/charts/${encodeURIComponent(chartId)}/query`;
    const body = filters && filters.length > 0 ? { filters } : {};
    const response = await this.request<unknown>(url, {
      method: 'POST',
      body: JSON.stringify(body),
      headers: { 'Content-Type': 'application/json' },
    });
    return this.normaliseChartResult(response);
  }

  /**
   * The server wraps the chart query response in `{chart_id, result}` (see
   * ChartQueryController). Older planning docs describe a flatter shape, so
   * accept either to stay forward-compatible.
   */
  private normaliseChartResult(payload: unknown): ChartResultPayload {
    if (!payload || typeof payload !== 'object') {
      throw new EmbedLayerApiError(
        200,
        'Chart query response is not an object.',
        payload,
      );
    }
    const record = payload as Record<string, unknown>;
    const candidate =
      record.result && typeof record.result === 'object'
        ? (record.result as Record<string, unknown>)
        : record;

    const columns = Array.isArray(candidate.columns) ? candidate.columns : [];
    const rows = Array.isArray(candidate.rows) ? candidate.rows : [];
    const metadata =
      candidate.metadata && typeof candidate.metadata === 'object'
        ? (candidate.metadata as Record<string, unknown>)
        : {};

    return {
      columns: columns as ChartResultPayload['columns'],
      rows: rows as ChartResultPayload['rows'],
      metadata,
    };
  }

  private async request<T>(url: string, init: RequestInit): Promise<T> {
    const headers = new Headers(init.headers);
    headers.set('Authorization', `Bearer ${this.token}`);
    headers.set('Accept', 'application/json');

    let attempt = 0;
    // 0 retries means a single attempt; maxRetries=2 means up to 3 attempts total.
    while (true) {
      let response: Response;
      try {
        response = await this.fetchImpl(url, { ...init, headers });
      } catch (err) {
        if (attempt >= this.maxRetries) {
          throw new EmbedLayerApiError(
            0,
            `Network error contacting embed API: ${(err as Error).message}`,
          );
        }
        await this.backoff(attempt);
        attempt += 1;
        continue;
      }

      if (response.ok) {
        return (await response.json()) as T;
      }

      // 4xx → fail fast; 5xx → retry up to maxRetries.
      if (response.status >= 500 && attempt < this.maxRetries) {
        await this.backoff(attempt);
        attempt += 1;
        continue;
      }

      const body = await this.safeReadBody(response);
      const message =
        (body && typeof body === 'object' && 'error' in body && typeof (body as { error: unknown }).error === 'string'
          ? (body as { error: string }).error
          : null) ?? `Embed API returned ${response.status}`;
      throw new EmbedLayerApiError(response.status, message, body);
    }
  }

  private async safeReadBody(response: Response): Promise<unknown> {
    try {
      return await response.json();
    } catch {
      return null;
    }
  }

  private backoff(attempt: number): Promise<void> {
    // 100ms, 300ms, … capped at 1s. Adds jitter to avoid thundering herd.
    const base = Math.min(1000, 100 * 3 ** attempt);
    const jitter = Math.random() * 50;
    return new Promise((resolve) => setTimeout(resolve, base + jitter));
  }
}
