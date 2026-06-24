import { mount, unmount } from 'svelte';
import EmbedLayerDashboard from './components/EmbedLayerDashboard.svelte';

export { EmbedLayerApiClient, EmbedLayerApiError } from './api/client.js';
export type {
  ChartColumn,
  ChartPayload,
  ChartResultPayload,
  ChartRow,
  ChartType,
  DashboardPayload,
  DashboardSummary,
  EmbedPayload,
  Filter,
  OrderByEntry,
  SemanticQuery,
  TimeDimension,
} from './types/index.js';

export interface RenderDashboardOptions {
  apiBaseUrl: string;
  token: string;
  embedId: string;
  dashboardId?: string;
}

interface MountedInstance {
  destroy(): void;
}

/**
 * Programmatic mount path. Returns a handle with `destroy()` so callers can
 * tear the dashboard down if the host page swaps it for something else.
 */
function renderDashboard(
  target: string | HTMLElement,
  opts: RenderDashboardOptions,
): MountedInstance {
  const el =
    typeof target === 'string'
      ? document.querySelector<HTMLElement>(target)
      : target;
  if (!el) {
    throw new Error(`EmbedLayer.renderDashboard: target not found (${String(target)})`);
  }

  const component = mount(EmbedLayerDashboard, {
    target: el,
    props: {
      apiBaseUrl: opts.apiBaseUrl,
      token: opts.token,
      embedId: opts.embedId,
      dashboardId: opts.dashboardId,
    },
  });

  return {
    destroy(): void {
      // mount() returns the component instance; unmount tears it down cleanly.
      void unmount(component);
    },
  };
}

/**
 * Custom element so the iframe view can drop a `<embed-layer-dashboard …>`
 * tag and the runtime auto-wires it. Attributes mirror the props.
 *
 * We hand-roll the element instead of using svelte-tag so the build stays
 * dependency-light. The element is non-reactive — changing attributes after
 * mount triggers a re-mount.
 */
class EmbedLayerDashboardElement extends HTMLElement {
  static get observedAttributes(): string[] {
    return ['api-base-url', 'token', 'embed-id', 'dashboard-id'];
  }

  private instance: ReturnType<typeof mount> | null = null;
  private host: HTMLElement | null = null;

  connectedCallback(): void {
    // Clear any "Loading…" fallback text the host page may have placed inside.
    this.textContent = '';
    this.host = document.createElement('div');
    this.host.style.display = 'block';
    this.host.style.width = '100%';
    this.appendChild(this.host);
    this.mountInstance();
  }

  disconnectedCallback(): void {
    this.unmountInstance();
    if (this.host && this.host.parentNode === this) {
      this.removeChild(this.host);
    }
    this.host = null;
  }

  attributeChangedCallback(
    _name: string,
    oldValue: string | null,
    newValue: string | null,
  ): void {
    if (oldValue === newValue || !this.isConnected || !this.host) {
      return;
    }
    this.unmountInstance();
    this.mountInstance();
  }

  private mountInstance(): void {
    if (!this.host) {
      return;
    }
    const apiBaseUrl = this.getAttribute('api-base-url') ?? '';
    const token = this.getAttribute('token') ?? '';
    const embedId = this.getAttribute('embed-id') ?? '';
    const dashboardId = this.getAttribute('dashboard-id') ?? undefined;

    if (!apiBaseUrl || !token || !embedId) {
      this.host.innerHTML = `
        <div style="padding:1rem;color:#b91c1c;background:#fef2f2;border-radius:.5rem;font-family:system-ui">
          <strong>EmbedLayer:</strong> missing required attribute
          (api-base-url, token, or embed-id).
        </div>`;
      return;
    }

    this.instance = mount(EmbedLayerDashboard, {
      target: this.host,
      props: { apiBaseUrl, token, embedId, dashboardId },
    });
  }

  private unmountInstance(): void {
    if (this.instance) {
      void unmount(this.instance);
      this.instance = null;
    }
    if (this.host) {
      this.host.innerHTML = '';
    }
  }
}

function registerElement(): void {
  if (typeof customElements === 'undefined') {
    return;
  }
  if (!customElements.get('embed-layer-dashboard')) {
    customElements.define('embed-layer-dashboard', EmbedLayerDashboardElement);
  }
}

// Auto-register when loaded as a classic <script src=…> tag (the iframe path).
registerElement();

// Surface the public API on `window.EmbedLayer` so non-web-component hosts
// can still mount programmatically.
declare global {
  interface Window {
    EmbedLayer?: {
      renderDashboard: typeof renderDashboard;
      registerElement: typeof registerElement;
    };
  }
}

if (typeof window !== 'undefined') {
  window.EmbedLayer = {
    renderDashboard,
    registerElement,
  };
}

export { renderDashboard, registerElement };
