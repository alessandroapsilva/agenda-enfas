# Arquitetura — SigasSaúde Enterprise

## Princípios

- Multi-tenant desde o primeiro dia: organização > unidade > setor > usuário.
- Zero Trust: autorização por papel, escopo e contexto clínico.
- Auditabilidade: toda ação sensível gera evento append-only.
- Integrações desacopladas: assinatura digital, TISS/TUSS e FHIR ficam atrás de contratos internos.
- Dados clínicos e documentos assinados nunca são sobrescritos; novas versões geram novos registros.
- IDs UUID em toda a plataforma.

## Domínios principais

### Identidade e acesso
RBAC + escopos por organização/unidade. Evolução recomendada: OIDC/SAML, MFA, WebAuthn, gestão de sessão e SCIM.

### Paciente
Cadastro mestre, documentos, contatos, convênios, responsáveis, consentimentos, alergias e alertas.

### Agenda
Profissionais, salas, recursos, encaixes, confirmação, fila de espera, recorrência, teleatendimento e check-in.

### Prontuário
Evolução, anamnese, sinais vitais, diagnósticos, problemas, alergias, procedimentos, documentos e anexos.

### Prescrição
MedicationRequest interno, itens, posologia, orientações, tipo de receituário, PDF nato-digital, assinatura e rastreabilidade.

### Assinatura
Contrato `SignatureProvider` para suportar A1, A3, certificado em nuvem e provedores terceiros, sem alterar o domínio clínico.

### Faturamento
Contas, pacotes, procedimentos, guias, lotes, glosas, recebíveis e conciliação. Adaptador TISS/TUSS separado.

### Interoperabilidade
Camada de tradução para HL7 FHIR e conectores externos. Nunca acoplar o modelo interno diretamente a um formato externo.

## Segurança recomendada para produção

- TLS 1.3 externo e mTLS entre serviços críticos.
- Criptografia em repouso com KMS/HSM.
- Segredos fora do repositório.
- Backups criptografados e testes periódicos de restauração.
- Logs imutáveis com retenção definida por política.
- DLP e mascaramento para ambientes de teste.
- MFA obrigatório para profissionais e administradores.
- Rate limiting, WAF e proteção contra abuso.
- SAST, DAST, dependency scanning e pentest antes de produção.
- Plano de resposta a incidentes e trilha de acesso ao prontuário.

## Escala internacional

Para expansão multinacional, usar um `country-pack` por país contendo regras clínicas, terminologias, modelos de documento, assinatura, fiscal, privacidade e integrações. O núcleo clínico permanece comum.
