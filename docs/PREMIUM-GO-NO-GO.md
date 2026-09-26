# Agenda ENFAS Premium — Go/No-Go

## Antes do deploy

- Confirmar branch `feature/agenda-premium` limpa e atualizada.
- Confirmar backup diário recente.
- Confirmar espaço em disco.
- Confirmar WABA/Meta configurados.
- Rodar `php artisan test`.
- Rodar `npm run build`.
- Rodar `php artisan route:list` e verificar rotas premium.
- Não alterar o arquivo `.env` no deploy.

## Deploy

Use somente depois da validação em homologação:

```bash
cd /home/agendaenfas/agenda-enfas-premium
bash scripts/deploy-premium.sh
```

## Pós-deploy

Validar:

1. Login.
2. Dashboard.
3. Agenda dia/semana/mês.
4. Criar agendamento simples.
5. Melhor horário.
6. Agendamento recorrente.
7. Cadastro de paciente.
8. Cadastro de profissional e disponibilidade.
9. Bloqueio/ausência.
10. Lista de espera.
11. Central de Atendimento.
12. Envio WhatsApp.
13. Botões Confirmar/Reagendar/Cancelar.
14. Notificação do profissional.
15. Usuários e permissões.
16. Relatórios e exportação CSV.
17. `php artisan enfas:production-check`.

## Rollback de arquivos

```bash
cd /home/agendaenfas/agenda-enfas-premium
bash scripts/rollback-premium.sh
```

O rollback de arquivos não executa `migrate:rollback` automaticamente para evitar perda de dados.
