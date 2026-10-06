# SigasSaúde Enterprise

Base arquitetural para uma plataforma clínica enterprise, multiempresa e multiunidade, preparada para prontuário eletrônico, agenda, prescrição eletrônica, assinatura digital, faturamento, TISS/TUSS, interoperabilidade e auditoria.

> **Importante:** esta entrega é a fundação técnica inicial. Recursos regulados como assinatura ICP-Brasil, TISS em produção, integração com operadoras, RNDS e autenticação forte exigem credenciais, homologações, testes e validação jurídica/segurança antes do uso assistencial real.

## Stack proposta

- Web: Next.js + TypeScript
- API: FastAPI + Python
- Dados: PostgreSQL
- Cache/filas: Redis
- Documentos clínicos: S3/MinIO
- Integrações: adaptadores independentes para assinatura ICP-Brasil, TISS/TUSS e FHIR/RNDS
- Infra: Docker Compose para desenvolvimento; Kubernetes/Terraform recomendados para produção

## Subir localmente

```bash
cp .env.example .env
docker compose up --build
```

Acesse:
- Web: http://localhost:3000
- API: http://localhost:8000
- Swagger: http://localhost:8000/docs
- MinIO Console: http://localhost:9001

## Módulos já desenhados na base

1. Health/observabilidade
2. Pacientes
3. Agenda
4. Prontuário eletrônico
5. Prescrição eletrônica
6. Assinatura digital por provider
7. Faturamento/TISS (estrutura para evolução)
8. Auditoria imutável de eventos clínicos
9. Multi-tenant por organização/unidade

Veja `docs/ARCHITECTURE.md` e `docs/ROADMAP.md`.

## Domínio de produção recomendado

No CloudPanel, publicar inicialmente em `https://sigassaude.enfas.com.br`, com a API atrás de `/api`. Veja `docs/CLOUDPANEL.md`.
