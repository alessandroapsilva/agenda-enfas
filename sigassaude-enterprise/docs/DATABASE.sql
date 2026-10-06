-- Esqueleto conceitual inicial. Migrar para Alembic antes de produção.
CREATE EXTENSION IF NOT EXISTS pgcrypto;

CREATE TABLE organizations (
  id uuid PRIMARY KEY DEFAULT gen_random_uuid(),
  legal_name text NOT NULL,
  trade_name text,
  country_code char(2) NOT NULL DEFAULT 'BR',
  created_at timestamptz NOT NULL DEFAULT now()
);

CREATE TABLE units (
  id uuid PRIMARY KEY DEFAULT gen_random_uuid(),
  organization_id uuid NOT NULL REFERENCES organizations(id),
  name text NOT NULL,
  cnes text,
  timezone text NOT NULL DEFAULT 'America/Sao_Paulo',
  created_at timestamptz NOT NULL DEFAULT now()
);

CREATE TABLE patients (
  id uuid PRIMARY KEY DEFAULT gen_random_uuid(),
  organization_id uuid NOT NULL REFERENCES organizations(id),
  full_name text NOT NULL,
  birth_date date,
  cpf text,
  metadata jsonb NOT NULL DEFAULT '{}'::jsonb,
  created_at timestamptz NOT NULL DEFAULT now()
);
CREATE INDEX idx_patients_org_name ON patients(organization_id, full_name);

CREATE TABLE clinical_documents (
  id uuid PRIMARY KEY DEFAULT gen_random_uuid(),
  organization_id uuid NOT NULL REFERENCES organizations(id),
  patient_id uuid NOT NULL REFERENCES patients(id),
  document_type text NOT NULL,
  object_uri text NOT NULL,
  sha256 text NOT NULL,
  signed boolean NOT NULL DEFAULT false,
  supersedes_id uuid REFERENCES clinical_documents(id),
  created_at timestamptz NOT NULL DEFAULT now()
);

CREATE TABLE audit_events (
  id bigserial PRIMARY KEY,
  occurred_at timestamptz NOT NULL DEFAULT now(),
  organization_id uuid NOT NULL REFERENCES organizations(id),
  actor_id text NOT NULL,
  action text NOT NULL,
  resource_type text NOT NULL,
  resource_id text,
  source_ip inet,
  details jsonb NOT NULL DEFAULT '{}'::jsonb
);
CREATE INDEX idx_audit_org_time ON audit_events(organization_id, occurred_at DESC);
