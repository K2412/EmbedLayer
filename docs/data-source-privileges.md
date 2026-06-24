# Read-only DB user setup per data-source driver

EmbedLayer is a read-only consumer of customer data. Plan §17.4 requires least-privilege credentials. Configure the user you put in the data-source config to have *only* `SELECT` on the schemas you intend to expose, and nothing else. Below are the GRANT / role commands per supported driver.

## Postgres

```sql
-- inside the customer's analytics database
CREATE ROLE embedlayer_ro WITH LOGIN PASSWORD 'change-me';

GRANT CONNECT ON DATABASE "<your-db>" TO embedlayer_ro;
GRANT USAGE ON SCHEMA public TO embedlayer_ro;
GRANT SELECT ON ALL TABLES IN SCHEMA public TO embedlayer_ro;

-- so future tables remain readable without re-granting
ALTER DEFAULT PRIVILEGES IN SCHEMA public GRANT SELECT ON TABLES TO embedlayer_ro;
```

Use `sslmode=require` (or stricter) in the data-source config in production.

## MySQL

```sql
CREATE USER 'embedlayer_ro'@'%' IDENTIFIED BY 'change-me';

GRANT SELECT ON `<your-db>`.* TO 'embedlayer_ro'@'%';

FLUSH PRIVILEGES;
```

If your MySQL is behind an IP allowlist, lock the host pattern further (e.g. `'embedlayer_ro'@'10.0.0.%'`).

## BigQuery

Create a dedicated service account with no project-wide editor roles:

```
gcloud iam service-accounts create embedlayer-ro \
  --display-name="EmbedLayer read-only"

gcloud projects add-iam-policy-binding <project-id> \
  --member="serviceAccount:embedlayer-ro@<project-id>.iam.gserviceaccount.com" \
  --role="roles/bigquery.dataViewer"

gcloud projects add-iam-policy-binding <project-id> \
  --member="serviceAccount:embedlayer-ro@<project-id>.iam.gserviceaccount.com" \
  --role="roles/bigquery.jobUser"
```

`bigquery.dataViewer` is required to read tables; `bigquery.jobUser` is required to execute queries (this role does **not** grant write access). Generate the JSON key, paste it into the data-source `service_account_json` field in the EmbedLayer UI, then **delete the local copy** — it stays encrypted in `analytics_data_sources.encrypted_config`.

## Snowflake

```sql
USE ROLE SECURITYADMIN;

CREATE ROLE embedlayer_ro;
CREATE USER embedlayer_ro
  PASSWORD = 'change-me'
  DEFAULT_ROLE = embedlayer_ro
  DEFAULT_WAREHOUSE = '<warehouse>';

GRANT USAGE ON WAREHOUSE "<warehouse>" TO ROLE embedlayer_ro;
GRANT USAGE ON DATABASE "<database>" TO ROLE embedlayer_ro;
GRANT USAGE ON SCHEMA "<database>"."<schema>" TO ROLE embedlayer_ro;
GRANT SELECT ON ALL TABLES IN SCHEMA "<database>"."<schema>" TO ROLE embedlayer_ro;
GRANT SELECT ON FUTURE TABLES IN SCHEMA "<database>"."<schema>" TO ROLE embedlayer_ro;

GRANT ROLE embedlayer_ro TO USER embedlayer_ro;
```

For higher-trust environments prefer key-pair authentication and store the private key in the encrypted config rather than a password.

## Audit

Every connection test, introspection, and query run is recorded in `analytics_query_runs` (organization scoped). Pair this with the credential-rotation command when you suspect compromise:

```
php artisan embedlayer:rotate-credentials
```
