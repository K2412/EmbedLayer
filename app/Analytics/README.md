# app/Analytics

The EmbedLayer analytics product, isolated from the rest of the Laravel app.

The directory layout in this namespace follows **§3 (Laravel Application Structure)** of the technical plan:

`../../../embedlayer_v1_v3_technical_plan.md`

Subtrees:

- `Actions/` — Use-case actions (per §3)
- `DataSources/` — Connector contract, driver implementations, catalog DTOs (§9)
- `Semantic/` — Provider contract, provider implementations, semantic DTOs (§5–§7)
- `Compiler/` — Deterministic SQL compilation + per-dialect rendering (§8)
- `Pipelines/` — Query execution pipeline + pipes (§10)
- `Security/` — Embed tokens, access policies, credential vault (§11)
- `Support/` — Value objects, enums, exceptions
