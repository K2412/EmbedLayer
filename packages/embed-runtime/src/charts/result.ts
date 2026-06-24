import type { ChartResultPayload, ChartRow } from '../types/chart.js';

/**
 * Pick the first numeric column from a result, falling back to the first
 * column if no numeric column exists. Used by NumberCard / BarChart / LineChart
 * when the chart payload doesn't pin a measure explicitly.
 */
export function firstNumericKey(result: ChartResultPayload): string | null {
  const fromColumns = result.columns.find((c) =>
    ['number', 'integer', 'float', 'decimal'].includes(c.type.toLowerCase()),
  );
  if (fromColumns) {
    return fromColumns.key;
  }

  const firstRow = result.rows[0];
  if (!firstRow) {
    return result.columns[0]?.key ?? null;
  }

  for (const key of Object.keys(firstRow)) {
    if (typeof firstRow[key] === 'number') {
      return key;
    }
  }

  return result.columns[0]?.key ?? null;
}

export function firstNonNumericKey(result: ChartResultPayload): string | null {
  const numericKey = firstNumericKey(result);
  const firstRow = result.rows[0];
  if (!firstRow) {
    return result.columns.find((c) => c.key !== numericKey)?.key ?? null;
  }
  for (const key of Object.keys(firstRow)) {
    if (key !== numericKey) {
      return key;
    }
  }
  return null;
}

export function asNumber(value: unknown): number | null {
  if (typeof value === 'number' && Number.isFinite(value)) {
    return value;
  }
  if (typeof value === 'string' && value.trim() !== '') {
    const parsed = Number(value);
    return Number.isFinite(parsed) ? parsed : null;
  }
  return null;
}

export function formatCell(value: unknown): string {
  if (value === null || value === undefined) {
    return '—';
  }
  if (typeof value === 'number') {
    return Number.isInteger(value)
      ? value.toLocaleString()
      : value.toLocaleString(undefined, { maximumFractionDigits: 4 });
  }
  if (typeof value === 'boolean') {
    return value ? 'true' : 'false';
  }
  if (value instanceof Date) {
    return value.toISOString();
  }
  return String(value);
}

export function rowValue(row: ChartRow, key: string): unknown {
  return Object.prototype.hasOwnProperty.call(row, key) ? row[key] : null;
}
