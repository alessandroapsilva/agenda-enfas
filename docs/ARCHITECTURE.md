# Arquitetura — SIGH ENFAS

O SIGH ENFAS começa como **monólito modular em Laravel 13**, com fronteiras claras entre domínios. Isso reduz complexidade operacional no CloudPanel e permite extrair serviços depois quando volume ou integração justificarem.

## Domínios

Identity & Access; Cadastro Mestre/MPI; Agenda e Recepção; Atendimento; PEP; Prescrição; Enfermagem; **SIGH FAR**; Estoque/Suprimentos; SADT/Laboratório; Internação; Centro Cirúrgico; CME; Faturamento; Financeiro; GED; BI; Integrações e Auditoria.

## Multiunidade

`Organization → Facility → Department/Unit → Resource`

O paciente mantém matrícula única e histórico longitudinal. Atendimentos e transações carregam o contexto da unidade onde ocorreram.

## Segurança

- MFA para perfis privilegiados;
- RBAC/ABAC por função, unidade e contexto;
- trilha de auditoria;
- menor privilégio;
- TLS;
- segredos fora do repositório;
- backup criptografado com teste de restauração;
- segregação entre produção e homologação;
- acesso emergencial auditado.

## Assinatura digital

Prescrições e documentos clínicos terão uma camada `SignatureProvider` desacoplada. Certificados A1, A3 ou nuvem entram por adaptadores; chaves privadas não ficam gravadas diretamente no banco.

## Eventos de domínio

PatientRegistered, AppointmentScheduled, EncounterStarted, ClinicalNoteSigned, PrescriptionSigned, PrescriptionReleasedToPharmacy, MedicationDispensed, InventoryBelowMinimum, ExamResultReleased, AdmissionCreated, PatientDischarged e InvoiceClosed.

## Integrações futuras

HL7/FHIR, TISS/TUSS, PACS/DICOM, laboratório, WhatsApp/e-mail/SMS, pagamentos, assinatura digital, webhooks e APIs externas.
