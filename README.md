# SIGH ENFAS

**Sistema de Informação e Gestão Hospitalar da ENFAS**

Plataforma HIS/PEP modular para clínica, ambulatório e operação hospitalar, com domínio de farmácia hospitalar **SIGH FAR**.

**Ambiente alvo:** `https://sigh.enfas.com.br`

## Fluxo central

`Paciente → Matrícula → Agenda/Recepção → Atendimento → PEP → Prescrição → SIGH FAR → Exames/Procedimentos → Faturamento → Indicadores`

## Módulos

Cadastro/Matrícula, Agenda, Recepção/Filas, Ambulatório, Pronto Atendimento, PEP, Prescrição, Enfermagem, SIGH FAR, Estoque/Suprimentos, SADT, Laboratório, Internação/Leitos, Centro Cirúrgico, CME, Faturamento, Financeiro, GED, BI, Administração/Auditoria, Portal do Paciente e Integrações.

## Fundação desta branch

- Laravel 13;
- API `/api/v1`;
- cadastro central de pacientes;
- atendimentos;
- evoluções clínicas;
- prescrições;
- farmácia central/satélite;
- medicamentos;
- lotes e validades;
- movimentação de estoque;
- dispensação vinculada a paciente/prescrição;
- arquitetura multiunidade;
- preparação para assinatura digital desacoplada;
- sem integração SNCR.

## Branch

`sigh-enfas-enterprise`

> A fundação ainda precisa de homologação funcional, segurança, perfis de acesso, auditoria e testes assistenciais antes de qualquer uso clínico real.
