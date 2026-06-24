# EmbedLayer — Setup for new contributors

This repo uses **bd (beads)** for issue tracking. `.beads/issues.jsonl` is the git-tracked source of truth; the local Dolt DB at `.beads/embeddeddolt/` is per-clone state. After cloning, you need to (a) populate the local DB from the JSONL and (b) wire the git hooks so it stays in sync.

## First-time setup

```bash
git clone https://github.com/K2412/EmbedLayer.git
cd EmbedLayer-web

# 1. PHP + JS dependencies
composer install
npm install
cd packages/embed-runtime && npm install && cd ../..

# 2. Environment
cp .env.example .env
php artisan key:generate
# Generate the embedlayer encryption + signing keys; set in .env:
#   EMBEDLAYER_CREDENTIAL_ENCRYPTION_KEY=base64:$(php -r "echo base64_encode(random_bytes(32));")
#   EMBEDLAYER_EMBED_SIGNING_KEY=$(php -r "echo bin2hex(random_bytes(32));")

# 3. Database
php artisan migrate

# 4. Beads — install hooks + seed local Dolt from the tracked JSONL
bd hooks install      # wires .git/hooks → .beads/hooks shims
bd import             # reads .beads/issues.jsonl into the local Dolt DB
bd ready              # confirm: open work shows up
```

`bd hooks install` enables the auto-sync flow: `pre-commit` re-exports `issues.jsonl`, `post-merge` re-imports it after `git pull`. So once you've done first-time setup, just `git pull` and the beads catch up automatically.

## Daily workflow

```bash
git pull              # post-merge hook runs bd import
bd ready              # find unblocked work
bd update <id> --claim
# ...code...
vendor/bin/pint --dirty --format agent
php artisan test --compact
bd close <id> --reason "what landed"
git add -A && git commit -m "..."   # pre-commit hook runs bd export
git push
```

## Sanity check

```bash
bd list --status open --limit 0 | head    # should list ~13 V2/V3 beads + the platform epic
bd list --status closed --limit 0 | wc -l # should show ~90
php artisan test --compact                # should be all green
```

## Where to start

- `bd ready` shows the next unblocked beads.
- V1 is complete (zero open V1 beads). The remaining open work is V2/V3.
- The plan document at `../embedlayer_v1_v3_technical_plan.md` is the architectural spec.
- `CLAUDE.md` and `AGENTS.md` document the project conventions; the **Beads Issue Tracker** section in either file has the bd quick reference.
